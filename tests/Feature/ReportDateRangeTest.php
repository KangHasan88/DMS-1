<?php

namespace Tests\Feature;

use App\Models\ArInvoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDateRangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_report_rejects_invalid_dates(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->from('/reports/sales')
            ->get('/reports/sales?start_date=not-a-date')
            ->assertRedirect('/reports/sales')
            ->assertSessionHasErrors('start_date');
    }

    public function test_report_rejects_reversed_date_range(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->from('/reports/sales')
            ->get('/reports/sales?start_date=2026-05-25&end_date=2026-05-01')
            ->assertRedirect('/reports/sales')
            ->assertSessionHasErrors('end_date');
    }

    public function test_report_export_uses_validated_date_range(): void
    {
        $user = $this->superAdmin();

        $response = $this->actingAs($user)
            ->get('/reports/export/sales?start_date=2026-05-01&end_date=2026-05-25');

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $content = $response->streamedContent();
        $this->assertStringContainsString('2026-05-01', $content);
        $this->assertStringContainsString('2026-05-25', $content);
    }

    public function test_inventory_report_shows_week_cover_and_slow_moving_insights(): void
    {
        $user = $this->superAdmin();

        $fastProduct = Product::create([
            'name' => 'Produk Jalan',
            'category' => 'Sayur',
            'price' => 10000,
            'base_price' => 7000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $fastProduct->id,
            'quantity' => 100,
            'min_stock' => 5,
            'max_stock' => 200,
        ]);

        $slowProduct = Product::create([
            'name' => 'Produk Diam',
            'category' => 'Buah',
            'price' => 12000,
            'base_price' => 8000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $slowProduct->id,
            'quantity' => 5,
            'min_stock' => 2,
            'max_stock' => 20,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMG202606020001',
            'delivery_date' => now()->toDateString(),
            'address' => 'Alamat Test',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'status' => Order::STATUS_DELIVERED,
            'subtotal' => 100000,
            'total' => 100000,
            'grand_total' => 100000,
        ]);
        $order->forceFill([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ])->save();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $fastProduct->id,
            'product_name' => $fastProduct->name,
            'price' => 10000,
            'quantity' => 10,
            'subtotal' => 100000,
        ]);

        $this->actingAs($user)
            ->get('/reports/inventory')
            ->assertOk()
            ->assertSee('Terjual 30 Hari')
            ->assertSee('Week Cover')
            ->assertSee('Stok Berlebih')
            ->assertSee('Belum Bergerak 30 Hari')
            ->assertSee('42.9 minggu')
            ->assertSee('Produk Jalan')
            ->assertSee('Produk Diam');
    }

    public function test_ar_aging_report_groups_open_receivables_by_due_date(): void
    {
        $user = $this->superAdmin();
        $asOfDate = '2026-06-22';

        $this->createArInvoice($user, 'INV-CURRENT', '2026-06-25', 100000);
        $this->createArInvoice($user, 'INV-OVER-10', '2026-06-12', 200000);
        $this->createArInvoice($user, 'INV-OVER-40', '2026-05-13', 300000);
        $this->createArInvoice($user, 'INV-PAID', '2026-05-01', 400000, ArInvoice::STATUS_PAID, 400000, 0);
        $this->createArInvoice($user, 'INV-VOID', '2026-05-01', 500000, ArInvoice::STATUS_VOID, 0, 500000);

        $this->actingAs($user)
            ->get('/reports/ar-aging?as_of_date=' . $asOfDate)
            ->assertOk()
            ->assertSee('Umur Piutang')
            ->assertSee('Belum Jatuh Tempo')
            ->assertSee('1-30 Hari')
            ->assertSee('31-60 Hari')
            ->assertSee('INV-CURRENT')
            ->assertSee('INV-OVER-10')
            ->assertSee('INV-OVER-40')
            ->assertSee('Rp 600.000')
            ->assertSee('Rp 500.000')
            ->assertDontSee('INV-PAID')
            ->assertDontSee('INV-VOID');
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function createArInvoice(
        User $user,
        string $invoiceNumber,
        string $dueDate,
        int $totalAmount,
        string $status = ArInvoice::STATUS_ISSUED,
        int $paidAmount = 0,
        ?int $outstandingAmount = null
    ): ArInvoice {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMGAR' . substr(md5($invoiceNumber), 0, 8),
            'delivery_date' => '2026-06-01',
            'address' => 'Alamat AR Aging',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'status' => Order::STATUS_DELIVERED,
            'subtotal' => $totalAmount,
            'total' => $totalAmount,
            'grand_total' => $totalAmount,
        ]);

        return ArInvoice::create([
            'invoice_number' => $invoiceNumber,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'invoice_date' => '2026-06-01',
            'due_date' => $dueDate,
            'status' => $status,
            'subtotal' => $totalAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'outstanding_amount' => $outstandingAmount ?? max(0, $totalAmount - $paidAmount),
            'issued_by' => $user->id,
            'issued_at' => now(),
        ]);
    }
}
