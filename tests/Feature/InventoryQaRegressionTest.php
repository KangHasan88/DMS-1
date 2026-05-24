<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\OutboundFoc;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InventoryQaRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_product_store_uses_unit_id_from_form(): void
    {
        $user = $this->superAdmin();
        $unit = Unit::firstOrCreate(
            ['code' => 'kg'],
            [
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'is_active' => true,
            ]
        );

        $this->actingAs($user)
            ->post('/products', [
                'name' => 'Bayam Test',
                'category' => 'Sayur',
                'unit_id' => $unit->id,
                'price' => 5000,
                'base_price' => 3000,
                'is_active' => '1',
            ])
            ->assertRedirect('/products');

        $this->assertDatabaseHas('products', [
            'name' => 'Bayam Test',
            'unit_id' => $unit->id,
        ]);
    }

    public function test_outbound_foc_rolls_back_when_stock_is_not_enough(): void
    {
        $user = $this->superAdmin();
        $product = Product::create([
            'name' => 'Kangkung Test',
            'category' => 'Sayur',
            'price' => 4000,
            'base_price' => 2500,
            'is_active' => true,
        ]);

        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->from('/outbound-focs/create')
            ->post('/outbound-focs', [
                'customer_name' => 'Customer Test',
                'foc_date' => now()->toDateString(),
                'reason' => OutboundFoc::REASON_SAMPLE,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ])
            ->assertRedirect('/outbound-focs/create')
            ->assertSessionHas('error');

        $this->assertSame(1, $product->stock()->first()->quantity);
        $this->assertDatabaseCount('outbound_focs', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_delivery_can_only_be_created_for_ready_orders(): void
    {
        $user = $this->superAdmin();
        $kurir = $this->superAdmin('kurir@example.test');
        $order = $this->createOrder(Order::STATUS_PAID);

        $this->actingAs($user)
            ->from('/deliveries/create')
            ->post('/deliveries', [
                'order_id' => $order->id,
                'kurir_id' => $kurir->id,
            ])
            ->assertRedirect('/deliveries/create')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('deliveries', 0);
        $this->assertSame(Order::STATUS_PAID, $order->refresh()->status);
    }

    public function test_all_registered_controller_methods_exist(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (!str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action);

            if (!method_exists($class, $method)) {
                $missing[] = $route->uri() . ' => ' . $action;
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_stock_transaction_documents_are_append_only(): void
    {
        $this->assertFalse(Route::has('direct-purchases.destroy'));
        $this->assertFalse(Route::has('outbound-focs.destroy'));
        $this->assertFalse(Route::has('outbound-returns.destroy'));
    }

    private function superAdmin(string $email = 'admin@example.test'): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function createOrder(string $status): Order
    {
        $customer = User::factory()->create(['email' => 'customer@example.test']);

        return Order::create([
            'user_id' => $customer->id,
            'order_number' => 'KMGTEST0001',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '06:00-09:00',
            'address' => 'Jl. Test No. 1',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'subtotal' => 0,
            'total' => 0,
            'status' => $status,
            'order_source' => Order::ORDER_SOURCE_ADMIN,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'discount_type' => Order::DISCOUNT_NONE,
            'discount_value' => 0,
            'discount_amount' => 0,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'include_ppn' => false,
            'ppn_rate' => 11,
            'ppn_amount' => 0,
            'grand_total' => 0,
        ]);
    }
}
