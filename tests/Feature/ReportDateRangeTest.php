<?php

namespace Tests\Feature;

use App\Models\ArInvoice;
use App\Models\ApInvoice;
use App\Models\ChartAccount;
use App\Models\Delivery;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPrincipal;
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

    public function test_sales_export_respects_principal_filter_at_item_level(): void
    {
        $user = $this->superAdmin();
        $principalA = ProductPrincipal::create(['code' => 'PRA', 'name' => 'Principal A', 'is_active' => true]);
        $principalB = ProductPrincipal::create(['code' => 'PRB', 'name' => 'Principal B', 'is_active' => true]);
        $productA = Product::create([
            'principal_id' => $principalA->id,
            'name' => 'Produk Principal A',
            'category' => 'Demo',
            'price' => 10000,
            'base_price' => 7000,
            'is_active' => true,
        ]);
        $productB = Product::create([
            'principal_id' => $principalB->id,
            'name' => 'Produk Principal B',
            'category' => 'Demo',
            'price' => 20000,
            'base_price' => 12000,
            'is_active' => true,
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMG-EXPORT-PRINCIPAL',
            'delivery_date' => '2026-06-15',
            'address' => 'Alamat Test',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'status' => Order::STATUS_DELIVERED,
            'subtotal' => 30000,
            'total' => 30000,
            'grand_total' => 30000,
        ]);
        $order->forceFill([
            'created_at' => '2026-06-15 10:00:00',
            'updated_at' => '2026-06-15 10:00:00',
        ])->save();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productA->id,
            'product_name' => $productA->name,
            'price' => 10000,
            'quantity' => 1,
            'subtotal' => 10000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productB->id,
            'product_name' => $productB->name,
            'price' => 20000,
            'quantity' => 1,
            'subtotal' => 20000,
        ]);

        $response = $this->actingAs($user)
            ->get('/reports/export/sales?start_date=2026-06-01&end_date=2026-06-30&principal_id=' . $principalA->id);

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Principal A', $content);
        $this->assertStringContainsString('Produk Principal A', $content);
        $this->assertStringContainsString('10000', $content);
        $this->assertStringNotContainsString('Produk Principal B', $content);
        $this->assertStringNotContainsString('20000', $content);
    }

    public function test_inventory_export_respects_principal_filter(): void
    {
        $user = $this->superAdmin();
        $principalA = ProductPrincipal::create(['code' => 'INV-A', 'name' => 'Inventory Principal A', 'is_active' => true]);
        $principalB = ProductPrincipal::create(['code' => 'INV-B', 'name' => 'Inventory Principal B', 'is_active' => true]);
        $productA = Product::create([
            'principal_id' => $principalA->id,
            'name' => 'Stok Principal A',
            'category' => 'Demo',
            'price' => 10000,
            'base_price' => 7000,
            'is_active' => true,
        ]);
        $productB = Product::create([
            'principal_id' => $principalB->id,
            'name' => 'Stok Principal B',
            'category' => 'Demo',
            'price' => 20000,
            'base_price' => 12000,
            'is_active' => true,
        ]);
        ProductStock::create(['product_id' => $productA->id, 'quantity' => 15, 'min_stock' => 5, 'max_stock' => 50]);
        ProductStock::create(['product_id' => $productB->id, 'quantity' => 25, 'min_stock' => 5, 'max_stock' => 50]);

        $response = $this->actingAs($user)
            ->get('/reports/export/inventory?principal_id=' . $principalA->id);

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Inventory Principal A', $content);
        $this->assertStringContainsString('Stok Principal A', $content);
        $this->assertStringNotContainsString('Stok Principal B', $content);
    }

    public function test_sales_report_uses_historical_order_totals_after_master_price_changes(): void
    {
        $user = $this->superAdmin();
        $product = Product::create([
            'name' => 'Produk Harga Historis',
            'category' => 'Demo',
            'price' => 10000,
            'base_price' => 7000,
            'is_active' => true,
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMG-HISTORICAL-PRICE',
            'delivery_date' => '2026-06-15',
            'address' => 'Alamat Test',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'status' => Order::STATUS_DELIVERED,
            'subtotal' => 100000,
            'total' => 100000,
            'grand_total' => 100000,
        ]);
        $order->forceFill([
            'created_at' => '2026-06-15 10:00:00',
            'updated_at' => '2026-06-15 10:00:00',
        ])->save();
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 10000,
            'purchase_price' => 7000,
            'quantity' => 10,
            'subtotal' => 100000,
        ]);

        $product->update([
            'price' => 20000,
            'base_price' => 15000,
        ]);

        $this->actingAs($user)
            ->get('/reports/sales?start_date=2026-06-01&end_date=2026-06-30')
            ->assertOk()
            ->assertSee('KMG-HISTORICAL-PRICE')
            ->assertSee('Rp 100.000')
            ->assertDontSee('Rp 200.000');

        $item->refresh();
        $order->refresh();

        $this->assertSame(10000, $item->price);
        $this->assertSame(7000, $item->purchase_price);
        $this->assertSame(100000, $item->subtotal);
        $this->assertSame(100000, $order->grand_total);
    }

    public function test_sales_report_filters_by_search_status_and_page_size(): void
    {
        $user = $this->superAdmin();
        $targetOrder = Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMG-SALES-FILTER-TARGET',
            'delivery_date' => '2026-06-15',
            'address' => 'Alamat Test',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'status' => Order::STATUS_DELIVERED,
            'subtotal' => 100000,
            'total' => 100000,
            'grand_total' => 100000,
        ]);
        $targetOrder->forceFill(['created_at' => '2026-06-15 10:00:00'])->save();

        $otherOrder = Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMG-SALES-FILTER-OTHER',
            'delivery_date' => '2026-06-15',
            'address' => 'Alamat Test',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'subtotal' => 50000,
            'total' => 50000,
            'grand_total' => 50000,
        ]);
        $otherOrder->forceFill(['created_at' => '2026-06-15 11:00:00'])->save();

        $this->actingAs($user)
            ->get('/reports/sales?start_date=2026-06-01&end_date=2026-06-30&search=TARGET&status=' . Order::STATUS_DELIVERED . '&per_page=10')
            ->assertOk()
            ->assertSee('KMG-SALES-FILTER-TARGET')
            ->assertSee('10 data')
            ->assertDontSee('KMG-SALES-FILTER-OTHER');
    }

    public function test_delivery_report_filters_by_search_status_and_page_size(): void
    {
        $user = $this->superAdmin();
        $kurir = User::factory()->create(['name' => 'Kurir Target']);
        $targetOrder = Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMG-DELIVERY-TARGET',
            'delivery_date' => '2026-06-15',
            'address' => 'Alamat Test',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'status' => Order::STATUS_SHIPPED,
            'subtotal' => 100000,
            'total' => 100000,
            'grand_total' => 100000,
        ]);
        $targetOrder->forceFill(['created_at' => '2026-06-15 10:00:00'])->save();
        $otherOrder = Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMG-DELIVERY-OTHER',
            'delivery_date' => '2026-06-15',
            'address' => 'Alamat Test',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'status' => Order::STATUS_SHIPPED,
            'subtotal' => 50000,
            'total' => 50000,
            'grand_total' => 50000,
        ]);
        $otherOrder->forceFill(['created_at' => '2026-06-15 11:00:00'])->save();

        Delivery::create([
            'order_id' => $targetOrder->id,
            'delivery_method' => Delivery::METHOD_INTERNAL,
            'kurir_id' => $kurir->id,
            'status' => Delivery::STATUS_COMPLETED,
        ]);
        Delivery::create([
            'order_id' => $otherOrder->id,
            'delivery_method' => Delivery::METHOD_INTERNAL,
            'kurir_id' => $kurir->id,
            'status' => Delivery::STATUS_ASSIGNED,
        ]);

        $this->actingAs($user)
            ->get('/reports/delivery?start_date=2026-06-01&end_date=2026-06-30&search=TARGET&status=' . Delivery::STATUS_COMPLETED . '&per_page=10')
            ->assertOk()
            ->assertSee('KMG-DELIVERY-TARGET')
            ->assertSee('10 data')
            ->assertDontSee('KMG-DELIVERY-OTHER');
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

    public function test_inventory_report_filters_by_search_category_insight_and_page_size(): void
    {
        $user = $this->superAdmin();
        $principal = ProductPrincipal::create(['code' => 'TEST-DANONE', 'name' => 'Danone Test', 'is_active' => true]);
        $outProduct = Product::create([
            'principal_id' => $principal->id,
            'name' => 'AQUA Botol Filter Test',
            'category' => 'Minuman',
            'price' => 50000,
            'base_price' => 42000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $outProduct->id,
            'quantity' => 0,
            'min_stock' => 5,
            'max_stock' => 50,
        ]);

        $otherProduct = Product::create([
            'principal_id' => $principal->id,
            'name' => 'Mizone Filter Test',
            'category' => 'Isotonik',
            'price' => 60000,
            'base_price' => 45000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $otherProduct->id,
            'quantity' => 30,
            'min_stock' => 5,
            'max_stock' => 50,
        ]);

        $this->actingAs($user)
            ->get('/reports/inventory?search=AQUA&category=Minuman&insight=out&per_page=10')
            ->assertOk()
            ->assertSee('AQUA Botol Filter Test')
            ->assertSee('Stok Habis')
            ->assertSee('10 data')
            ->assertDontSee('Mizone Filter Test');
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
        $cash = $this->account('1110', 'Kas dan Bank', ChartAccount::TYPE_ASSET, isCashAccount: true);
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
            ->assertSee('Arus Kas')
            ->assertSee('Kas Masuk')
            ->assertSee('Kas Keluar')
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

    public function test_financial_export_contains_profit_loss_and_balance_sheet_rows(): void
    {
        $user = $this->superAdmin();
        $cash = $this->account('1110', 'Kas dan Bank', ChartAccount::TYPE_ASSET, isCashAccount: true);
        $revenue = $this->account('4101', 'Pendapatan Penjualan', ChartAccount::TYPE_REVENUE);

        $this->postJournal('2026-06-10', [
            [$cash, 150000, 0],
            [$revenue, 0, 150000],
        ]);

        $response = $this->actingAs($user)
            ->get('/reports/export/financial?start_date=2026-06-01&end_date=2026-06-30');

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $content = $response->streamedContent();

        $this->assertStringContainsString('Laporan Keuangan', $content);
        $this->assertStringContainsString('Laba Rugi', $content);
        $this->assertStringContainsString('Neraca', $content);
        $this->assertStringContainsString('Arus Kas', $content);
        $this->assertStringContainsString('Pendapatan Penjualan', $content);
        $this->assertStringContainsString('Kas dan Bank', $content);
        $this->assertStringContainsString('150000', $content);
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

    private function account(string $code, string $name, string $type, bool $isCashAccount = false): ChartAccount
    {
        return ChartAccount::create([
            'code' => $code,
            'name' => $name,
            'account_type' => $type,
            'normal_balance' => ChartAccount::defaultNormalBalance($type),
            'is_cash_account' => $isCashAccount,
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
