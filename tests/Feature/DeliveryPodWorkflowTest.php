<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\CompanyProfile;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryPodWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_delivery_cannot_be_completed_without_minimal_pod(): void
    {
        $admin = $this->admin();
        $delivery = $this->delivery(Delivery::STATUS_IN_TRANSIT, [
            'picked_up_at' => now()->subHour(),
            'in_transit_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($admin)->post(route('deliveries.update-status', $delivery), [
            'status' => Delivery::STATUS_COMPLETED,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('deliveries', [
            'id' => $delivery->id,
            'status' => Delivery::STATUS_IN_TRANSIT,
            'pod_receiver_name' => null,
        ]);
    }

    public function test_delivery_can_be_completed_with_receiver_name_as_pod(): void
    {
        $admin = $this->admin();
        $delivery = $this->delivery(Delivery::STATUS_IN_TRANSIT, [
            'picked_up_at' => now()->subHour(),
            'in_transit_at' => now()->subMinutes(30),
        ]);

        $this->actingAs($admin)->post(route('deliveries.update-status', $delivery), [
            'status' => Delivery::STATUS_COMPLETED,
            'pod_receiver_name' => 'Budi Gudang',
            'notes' => 'Diterima bagian gudang.',
        ])->assertRedirect(route('deliveries.show', $delivery));

        $delivery->refresh();
        $this->assertSame(Delivery::STATUS_COMPLETED, $delivery->status);
        $this->assertSame('Budi Gudang', $delivery->pod_receiver_name);
        $this->assertNotNull($delivery->pod_received_at);
        $this->assertSame(Order::STATUS_DELIVERED, $delivery->order->fresh()->status);
    }

    public function test_delivery_can_be_marked_failed_with_reason_without_delivering_order(): void
    {
        $admin = $this->admin();
        $delivery = $this->delivery(Delivery::STATUS_IN_TRANSIT, [
            'picked_up_at' => now()->subHour(),
            'in_transit_at' => now()->subMinutes(30),
        ]);

        $this->actingAs($admin)->post(route('deliveries.update-status', $delivery), [
            'status' => Delivery::STATUS_FAILED,
            'failure_reason' => 'Toko tutup saat kurir sampai.',
        ])->assertRedirect(route('deliveries.show', $delivery));

        $delivery->refresh();
        $this->assertSame(Delivery::STATUS_FAILED, $delivery->status);
        $this->assertSame('Toko tutup saat kurir sampai.', $delivery->failure_reason);
        $this->assertNotNull($delivery->failed_at);
        $this->assertSame(Order::STATUS_SHIPPED, $delivery->order->fresh()->status);
        $this->assertStringContainsString('Pengiriman gagal', (string) $delivery->order->fresh()->admin_notes);
    }

    public function test_delivery_expense_can_be_posted_to_cash_bank_with_delivery_reference(): void
    {
        $admin = $this->admin();
        $delivery = $this->delivery(Delivery::STATUS_IN_TRANSIT, [
            'picked_up_at' => now()->subHour(),
            'in_transit_at' => now()->subMinutes(30),
        ]);

        $cash = ChartAccount::create([
            'code' => '1101-POD',
            'name' => 'Kas POD',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'is_cash_account' => true,
            'is_active' => true,
        ]);
        $expense = ChartAccount::create([
            'code' => '6101-POD',
            'name' => 'Biaya Delivery POD',
            'account_type' => ChartAccount::TYPE_EXPENSE,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'is_cash_account' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('cash-bank.expenses.store'), [
            'transaction_date' => now()->toDateString(),
            'cash_account_id' => $cash->id,
            'expense_account_id' => $expense->id,
            'amount' => 25000,
            'reference_number' => 'DLV-' . $delivery->id,
            'description' => 'Biaya BBM delivery',
            'return_delivery_id' => $delivery->id,
        ])->assertRedirect(route('deliveries.show', $delivery));

        $this->assertDatabaseHas('journal_entries', [
            'description' => 'Biaya Operasional - Biaya BBM delivery (DLV-' . $delivery->id . ')',
            'status' => 'posted',
            'debit_total' => 25000,
            'credit_total' => 25000,
        ]);
    }
    private function admin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function delivery(string $status, array $attributes = []): Delivery
    {
        $branch = CompanyProfile::defaultProfile()->defaultInvoiceBranch();
        $customer = User::factory()->create(['is_active' => true]);

        $order = Order::create([
            'user_id' => $customer->id,
            'company_branch_id' => $branch->id,
            'order_number' => 'POD-' . now()->format('His') . random_int(100, 999),
            'status' => Order::STATUS_SHIPPED,
            'payment_status' => 'paid',
            'payment_timing' => Order::PAYMENT_TIMING_PRE_PAID,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_method' => Order::PAYMENT_MANUAL,
            'order_source' => Order::ORDER_SOURCE_ADMIN,
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '06:00-09:00',
            'subtotal' => 100000,
            'total' => 100000,
            'grand_total' => 100000,
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'discount_type' => Order::DISCOUNT_NONE,
            'discount_value' => 0,
            'discount_amount' => 0,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'include_ppn' => false,
            'ppn_rate' => 0,
            'ppn_amount' => 0,
            'address' => 'Jl. POD Test',
        ]);

        return Delivery::create(array_merge([
            'order_id' => $order->id,
            'delivery_method' => Delivery::METHOD_INTERNAL,
            'status' => $status,
            'assigned_at' => now()->subHours(2),
            'actual_shipping_cost' => 0,
            'shipping_cost_status' => Delivery::COST_NOT_APPLICABLE,
        ], $attributes));
    }
}