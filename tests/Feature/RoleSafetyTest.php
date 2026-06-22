<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_role_cannot_be_renamed(): void
    {
        $user = $this->userWithRole('super-admin');
        $role = Role::findByName('super-admin');

        $response = $this->actingAs($user)->put(route('roles.update', $role), [
            'name' => 'owner',
            'permissions' => ['view dashboard'],
        ]);

        $response
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('error', 'Role sistem tidak dapat diedit.');

        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
        $this->assertDatabaseMissing('roles', ['name' => 'owner']);
    }

    public function test_super_admin_role_permissions_cannot_be_changed(): void
    {
        $user = $this->userWithRole('super-admin');
        $role = Role::findByName('super-admin');
        $permissionCount = $role->permissions()->count();

        $response = $this->actingAs($user)->put(route('roles.permissions.update', $role), [
            'permissions' => ['view dashboard'],
        ]);

        $response
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('error', 'Permission role sistem tidak dapat diubah.');

        $this->assertSame($permissionCount, $role->fresh()->permissions()->count());
    }

    public function test_role_helpers_match_order_and_delivery_process_boundaries(): void
    {
        $operator = $this->userWithRole('operator');
        $kurir = $this->userWithRole('kurir');
        $warehouse = $this->userWithRole('warehouse');
        $finance = $this->userWithRole('finance');
        $sales = $this->userWithRole('sales');

        $this->assertTrue($operator->canProcessOrders());
        $this->assertTrue($operator->isHelper());
        $this->assertFalse($operator->canAllocateStock());
        $this->assertTrue($operator->canPickOrders());
        $this->assertTrue($operator->canPackOrders());
        $this->assertTrue($operator->canProcessProcurement());
        $this->assertFalse($operator->canProcessDeliveries());

        $this->assertFalse($kurir->canProcessOrders());
        $this->assertTrue($kurir->isDriver());
        $this->assertFalse($kurir->canPickOrders());
        $this->assertFalse($kurir->canProcessFinance());
        $this->assertTrue($kurir->canProcessDeliveries());

        $this->assertTrue($warehouse->canProcessOrders());
        $this->assertTrue($warehouse->canAllocateStock());
        $this->assertFalse($warehouse->canPickOrders());
        $this->assertFalse($warehouse->canPackOrders());
        $this->assertFalse($warehouse->canProcessDeliveries());

        $this->assertFalse($finance->canProcessOrders());
        $this->assertFalse($finance->canProcessDeliveries());
        $this->assertFalse($finance->canAllocateStock());
        $this->assertFalse($finance->canPickOrders());
        $this->assertTrue($finance->canProcessFinance());

        $this->assertFalse($sales->canProcessOrders());
        $this->assertFalse($sales->canProcessDeliveries());
        $this->assertFalse($sales->canAllocateStock());
        $this->assertFalse($sales->canProcessFinance());
    }

    public function test_finance_can_confirm_prepaid_order_without_process_order_permission(): void
    {
        $finance = $this->userWithRole('finance');
        $order = $this->createOrder(Order::STATUS_PENDING_PAYMENT, Order::PAYMENT_TIMING_PRE_PAID);

        $this->actingAs($finance)
            ->post(route('orders.confirm-payment', $order), [
                'notes' => 'Pembayaran diterima oleh finance',
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame(Order::STATUS_PAID, $order->refresh()->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_finance_can_confirm_postpaid_order_after_shipped(): void
    {
        $finance = $this->userWithRole('finance');
        $order = $this->createOrder(Order::STATUS_SHIPPED, Order::PAYMENT_TIMING_POST_PAID);

        $this->actingAs($finance)
            ->post(route('orders.confirm-payment', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame(Order::STATUS_PAID, $order->refresh()->status);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createOrder(string $status, string $paymentTiming): Order
    {
        return Order::create([
            'user_id' => User::factory()->create()->id,
            'order_number' => 'KMGTEST' . str_pad((string) Order::count(), 4, '0', STR_PAD_LEFT),
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '06:00-09:00',
            'address' => 'Jl. Test No. 1',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'subtotal' => 10000,
            'total' => 10000,
            'status' => $status,
            'order_source' => Order::ORDER_SOURCE_ADMIN,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_method' => 'manual',
            'payment_timing' => $paymentTiming,
            'discount_type' => Order::DISCOUNT_NONE,
            'discount_value' => 0,
            'discount_amount' => 0,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'include_ppn' => false,
            'ppn_rate' => 11,
            'ppn_amount' => 0,
            'grand_total' => 10000,
        ]);
    }
}
