<?php

namespace Tests\Feature;

use App\Models\ArInvoice;
use App\Models\ApInvoice;
use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
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

    public function test_ap_aging_report_groups_open_payables_by_due_date(): void
    {
        $user = $this->superAdmin();
        $asOfDate = '2026-06-22';

        $this->createApInvoice($user, 'AP-CURRENT', '2026-06-25', 120000);
        $this->createApInvoice($user, 'AP-OVER-10', '2026-06-12', 220000);
        $this->createApInvoice($user, 'AP-OVER-40', '2026-05-13', 320000);
        $this->createApInvoice($user, 'AP-PAID', '2026-05-01', 420000, ApInvoice::STATUS_PAID, 420000, 0);
        $this->createApInvoice($user, 'AP-VOID', '2026-05-01', 520000, ApInvoice::STATUS_VOID, 0, 520000);

        $this->actingAs($user)
            ->get('/reports/ap-aging?as_of_date=' . $asOfDate)
            ->assertOk()
            ->assertSee('Umur Hutang')
            ->assertSee('Belum Jatuh Tempo')
            ->assertSee('1-30 Hari')
            ->assertSee('31-60 Hari')
            ->assertSee('AP-CURRENT')
            ->assertSee('AP-OVER-10')
            ->assertSee('AP-OVER-40')
            ->assertSee('Rp 660.000')
            ->assertSee('Rp 540.000')
            ->assertDontSee('AP-PAID')
            ->assertDontSee('AP-VOID');
    }

    public function test_financial_report_uses_posted_journals_for_profit_loss_and_balance_sheet(): void
    {
        $user = $this->superAdmin();
        $cash = $this->account('1110', 'Kas dan Bank', ChartAccount::TYPE_ASSET);
        $revenue = $this->account('4101', 'Pendapatan Penjualan', ChartAccount::TYPE_REVENUE);
        $expense = $this->account('6101', 'Beban Operasional', ChartAccount::TYPE_EXPENSE);

        $this->postJournal('2026-06-10', [
            [$cash, 150000, 0],
            [$revenue, 0, 150000],
        ]);
        $this->postJournal('2026-06-12', [
            [$expense, 40000, 0],
            [$cash, 0, 40000],
        ]);

        $this->actingAs($user)
            ->get('/reports/financial?start_date=2026-06-01&end_date=2026-06-30')
            ->assertOk()
            ->assertSee('Laporan Keuangan')
            ->assertSee('Laba Rugi')
            ->assertSee('Neraca')
            ->assertSee('Pendapatan Penjualan')
            ->assertSee('Beban Operasional')
            ->assertSee('Kas dan Bank')
            ->assertSee('Rp 150.000')
            ->assertSee('Rp 40.000')
            ->assertSee('Rp 110.000')
            ->assertSee('Balance')
            ->assertSee(route('general-ledger.index', [
                'chart_account_id' => $cash->id,
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertSee(route('general-ledger.index', [
                'chart_account_id' => $revenue->id,
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]));
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

    private function createApInvoice(
        User $user,
        string $invoiceNumber,
        string $dueDate,
        int $totalAmount,
        string $status = ApInvoice::STATUS_ISSUED,
        int $paidAmount = 0,
        ?int $outstandingAmount = null
    ): ApInvoice {
        $supplier = Supplier::create([
            'name' => 'Pemasok Aging ' . substr(md5($invoiceNumber), 0, 6),
            'phone' => '0812' . random_int(10000000, 99999999),
            'category' => Supplier::CATEGORY_ALL,
            'is_active' => true,
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'POAGING' . substr(md5($invoiceNumber), 0, 8),
            'supplier_id' => $supplier->id,
            'order_date' => '2026-06-01',
            'expected_delivery_date' => '2026-06-01',
            'received_date' => '2026-06-01',
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'subtotal' => $totalAmount,
            'total' => $totalAmount,
            'created_by' => $user->id,
        ]);

        return ApInvoice::create([
            'invoice_number' => $invoiceNumber,
            'purchase_order_id' => $purchaseOrder->id,
            'supplier_id' => $supplier->id,
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

    private function account(string $code, string $name, string $type): ChartAccount
    {
        return ChartAccount::create([
            'code' => $code,
            'name' => $name,
            'account_type' => $type,
            'normal_balance' => ChartAccount::defaultNormalBalance($type),
            'is_active' => true,
        ]);
    }

    private function postJournal(string $date, array $lines): JournalEntry
    {
        $debitTotal = collect($lines)->sum(fn ($line) => (int) $line[1]);
        $creditTotal = collect($lines)->sum(fn ($line) => (int) $line[2]);

        $journal = JournalEntry::create([
            'journal_number' => 'JRN-' . uniqid(),
            'journal_date' => $date,
            'description' => 'Financial report test',
            'status' => JournalEntry::STATUS_POSTED,
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
        ]);

        foreach ($lines as [$account, $debit, $credit]) {
            $journal->lines()->create([
                'chart_account_id' => $account->id,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
            ]);
        }

        return $journal;
    }
}
