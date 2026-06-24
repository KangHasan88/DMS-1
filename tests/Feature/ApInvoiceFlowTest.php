<?php

namespace Tests\Feature;

use App\Models\ApInvoice;
use App\Models\ApDebitNote;
use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_finance_can_issue_ap_invoice_from_received_purchase_order(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap@example.test');
        [$purchaseOrder, $supplier] = $this->receivedPurchaseOrder();

        $this->actingAs($finance)
            ->post(route('ap-invoices.store'), ['purchase_order_id' => $purchaseOrder->id])
            ->assertRedirect();

        $invoice = ApInvoice::with('items')->firstOrFail();

        $this->assertSame($purchaseOrder->id, $invoice->purchase_order_id);
        $this->assertSame($supplier->id, $invoice->supplier_id);
        $this->assertSame(60000, $invoice->total_amount);
        $this->assertSame(60000, $invoice->outstanding_amount);
        $this->assertSame(ApInvoice::STATUS_ISSUED, $invoice->status);
        $this->assertCount(1, $invoice->items);
        $this->assertSame('Produk AP', $invoice->items->first()->description);

        $journal = JournalEntry::with('lines.account')
            ->where('source_type', ApInvoice::class)
            ->where('source_id', $invoice->id)
            ->firstOrFail();

        $this->assertSame(60000, $journal->debit_total);
        $this->assertSame(60000, $journal->credit_total);
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '1301' && $line->debit_amount === 60000));
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '2101' && $line->credit_amount === 60000));

        $this->actingAs($finance)
            ->get(route('ap-invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('Produk AP');
    }

    public function test_ap_invoice_cannot_be_issued_before_purchase_order_is_received(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-not-ready@example.test');
        [$purchaseOrder] = $this->receivedPurchaseOrder(PurchaseOrder::STATUS_PENDING, 0);

        $this->actingAs($finance)
            ->from(route('ap-invoices.index'))
            ->post(route('ap-invoices.store'), ['purchase_order_id' => $purchaseOrder->id])
            ->assertRedirect(route('ap-invoices.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ap_invoices', 0);
    }

    public function test_ap_invoice_menu_is_available_from_sidebar(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-sidebar@example.test');

        $this->actingAs($finance)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Invoice AP')
            ->assertSee('Pembayaran Supplier');
    }

    public function test_finance_can_record_partial_supplier_payment_for_ap_invoice(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-payment-partial@example.test');
        [$purchaseOrder] = $this->receivedPurchaseOrder();
        $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, $finance);

        $this->actingAs($finance)
            ->post(route('supplier-payments.store'), [
                'ap_invoice_id' => $invoice->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => SupplierPayment::METHOD_TRANSFER,
                'amount' => 25000,
                'reference_number' => 'PAY-SUP-001',
                'notes' => 'Bayar sebagian',
            ])
            ->assertRedirect(route('ap-invoices.show', $invoice->fresh()));

        $invoice->refresh();
        $payment = SupplierPayment::with('allocations')->firstOrFail();

        $this->assertSame(25000, $payment->amount);
        $this->assertSame(0, $payment->unallocated_amount);
        $this->assertCount(1, $payment->allocations);
        $this->assertSame(25000, $invoice->paid_amount);
        $this->assertSame(35000, $invoice->outstanding_amount);
        $this->assertSame(ApInvoice::STATUS_PARTIALLY_PAID, $invoice->status);

        $journal = JournalEntry::with('lines.account')
            ->where('source_type', SupplierPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();

        $this->assertSame(25000, $journal->debit_total);
        $this->assertSame(25000, $journal->credit_total);
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '2101' && $line->debit_amount === 25000));
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '1110' && $line->credit_amount === 25000));
    }

    public function test_finance_can_post_ap_debit_note_and_reduce_invoice_outstanding(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-debit-note@example.test');
        [$purchaseOrder] = $this->receivedPurchaseOrder();
        $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, $finance);

        $this->actingAs($finance)
            ->post(route('ap-debit-notes.store'), [
                'ap_invoice_id' => $invoice->id,
                'note_date' => now()->toDateString(),
                'reason_type' => ApDebitNote::REASON_PURCHASE_RETURN,
                'amount' => 15000,
                'reference_number' => 'RTR-SUP-001',
                'notes' => 'Retur barang rusak',
            ])
            ->assertRedirect(route('ap-invoices.show', $invoice->fresh()));

        $invoice->refresh();
        $debitNote = ApDebitNote::firstOrFail();

        $this->assertSame(15000, $debitNote->amount);
        $this->assertSame(ApDebitNote::STATUS_POSTED, $debitNote->status);
        $this->assertSame(15000, $invoice->debit_note_amount);
        $this->assertSame(45000, $invoice->outstanding_amount);
        $this->assertSame(ApInvoice::STATUS_ISSUED, $invoice->status);

        $journal = JournalEntry::with('lines.account')
            ->where('source_type', ApDebitNote::class)
            ->where('source_id', $debitNote->id)
            ->firstOrFail();

        $this->assertSame(15000, $journal->debit_total);
        $this->assertSame(15000, $journal->credit_total);
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '2101' && $line->debit_amount === 15000));
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '5102' && $line->credit_amount === 15000));

        $this->actingAs($finance)
            ->get(route('ap-debit-notes.index'))
            ->assertOk()
            ->assertSee($debitNote->note_number)
            ->assertSee('Retur Pembelian');
    }

    public function test_ap_debit_note_can_be_voided_and_restores_invoice_outstanding(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-debit-note-void@example.test');
        [$purchaseOrder] = $this->receivedPurchaseOrder();
        $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, $finance);
        $debitNote = ApDebitNote::postForInvoice($invoice, [
            'note_date' => now()->toDateString(),
            'reason_type' => ApDebitNote::REASON_PRICE_ADJUSTMENT,
            'amount' => 10000,
        ], $finance);

        $this->actingAs($finance)
            ->post(route('ap-debit-notes.void', $debitNote), [
                'void_reason' => 'Salah koreksi',
            ])
            ->assertRedirect(route('ap-invoices.show', $invoice));

        $invoice->refresh();
        $debitNote->refresh();
        $originalJournal = JournalEntry::where('source_type', ApDebitNote::class)
            ->where('source_id', $debitNote->id)
            ->firstOrFail();
        $reversal = JournalEntry::where('source_type', JournalEntry::class)
            ->where('source_id', $originalJournal->id)
            ->firstOrFail();

        $this->assertSame(ApDebitNote::STATUS_VOID, $debitNote->status);
        $this->assertSame('Salah koreksi', $debitNote->void_reason);
        $this->assertSame(0, $invoice->debit_note_amount);
        $this->assertSame(60000, $invoice->outstanding_amount);
        $this->assertSame(JournalEntry::STATUS_VOID, $originalJournal->fresh()->status);
        $this->assertSame(10000, $reversal->debit_total);
        $this->assertSame(10000, $reversal->credit_total);
    }

    public function test_full_supplier_payment_marks_ap_invoice_paid(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-payment-full@example.test');
        [$purchaseOrder] = $this->receivedPurchaseOrder();
        $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, $finance);

        $this->actingAs($finance)
            ->post(route('supplier-payments.store'), [
                'ap_invoice_id' => $invoice->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => SupplierPayment::METHOD_CASH,
                'amount' => 60000,
            ])
            ->assertRedirect(route('ap-invoices.show', $invoice->fresh()));

        $invoice->refresh();

        $this->assertSame(60000, $invoice->paid_amount);
        $this->assertSame(0, $invoice->outstanding_amount);
        $this->assertSame(ApInvoice::STATUS_PAID, $invoice->status);
    }

    public function test_supplier_payment_cannot_exceed_ap_invoice_outstanding(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-payment-over@example.test');
        [$purchaseOrder] = $this->receivedPurchaseOrder();
        $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, $finance);

        $this->actingAs($finance)
            ->from(route('ap-invoices.show', $invoice))
            ->post(route('supplier-payments.store'), [
                'ap_invoice_id' => $invoice->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => SupplierPayment::METHOD_TRANSFER,
                'amount' => 70000,
            ])
            ->assertRedirect(route('ap-invoices.show', $invoice))
            ->assertSessionHas('error');

        $invoice->refresh();

        $this->assertDatabaseCount('supplier_payments', 0);
        $this->assertSame(0, $invoice->paid_amount);
        $this->assertSame(60000, $invoice->outstanding_amount);
    }

    public function test_finance_can_void_supplier_payment_and_restore_ap_invoice_outstanding(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-payment-void@example.test');
        [$purchaseOrder] = $this->receivedPurchaseOrder();
        $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, $finance);
        $payment = SupplierPayment::payForInvoice($invoice, [
            'payment_date' => now()->toDateString(),
            'payment_method' => SupplierPayment::METHOD_TRANSFER,
            'amount' => 20000,
        ], $finance);

        $this->actingAs($finance)
            ->post(route('supplier-payments.void', $payment), [
                'void_reason' => 'Salah nominal',
            ])
            ->assertRedirect(route('ap-invoices.show', $invoice));

        $invoice->refresh();
        $payment->refresh();
        $originalJournal = JournalEntry::where('source_type', SupplierPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();
        $reversal = JournalEntry::where('source_type', JournalEntry::class)
            ->where('source_id', $originalJournal->id)
            ->firstOrFail();

        $this->assertSame(SupplierPayment::STATUS_VOID, $payment->status);
        $this->assertSame('Salah nominal', $payment->void_reason);
        $this->assertSame(0, $invoice->paid_amount);
        $this->assertSame(60000, $invoice->outstanding_amount);
        $this->assertSame(ApInvoice::STATUS_ISSUED, $invoice->status);
        $this->assertSame(JournalEntry::STATUS_VOID, $originalJournal->fresh()->status);
        $this->assertSame(20000, $reversal->debit_total);
        $this->assertSame(20000, $reversal->credit_total);

        $this->actingAs($finance)
            ->get(route('supplier-payments.index', ['status' => SupplierPayment::STATUS_VOID]))
            ->assertOk()
            ->assertSee($payment->payment_number)
            ->assertSee('Void');
    }

    public function test_finance_can_void_unpaid_ap_invoice_with_reversal_entry(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-void@example.test');
        [$purchaseOrder] = $this->receivedPurchaseOrder();
        $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, $finance);

        $this->actingAs($finance)
            ->post(route('ap-invoices.void', $invoice), [
                'void_reason' => 'Salah terbit AP invoice',
            ])
            ->assertRedirect(route('ap-invoices.show', $invoice));

        $invoice->refresh();
        $originalJournal = JournalEntry::where('source_type', ApInvoice::class)
            ->where('source_id', $invoice->id)
            ->firstOrFail();
        $reversal = JournalEntry::where('source_type', JournalEntry::class)
            ->where('source_id', $originalJournal->id)
            ->firstOrFail();

        $this->assertSame(ApInvoice::STATUS_VOID, $invoice->status);
        $this->assertSame(0, $invoice->paid_amount);
        $this->assertSame(0, $invoice->outstanding_amount);
        $this->assertSame('Salah terbit AP invoice', $invoice->void_reason);
        $this->assertSame(JournalEntry::STATUS_VOID, $originalJournal->fresh()->status);
        $this->assertSame(60000, $reversal->debit_total);
        $this->assertSame(60000, $reversal->credit_total);
    }

    public function test_ap_invoice_with_active_payment_must_void_payment_first(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-void-blocked@example.test');
        [$purchaseOrder] = $this->receivedPurchaseOrder();
        $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, $finance);
        SupplierPayment::payForInvoice($invoice, [
            'payment_date' => now()->toDateString(),
            'payment_method' => SupplierPayment::METHOD_TRANSFER,
            'amount' => 20000,
        ], $finance);

        $this->actingAs($finance)
            ->from(route('ap-invoices.show', $invoice))
            ->post(route('ap-invoices.void', $invoice), [
                'void_reason' => 'Salah terbit AP invoice',
            ])
            ->assertRedirect(route('ap-invoices.show', $invoice))
            ->assertSessionHas('error');

        $this->assertSame(ApInvoice::STATUS_PARTIALLY_PAID, $invoice->fresh()->status);
    }

    public function test_branch_finance_only_sees_own_branch_ap_invoices(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $finance = $this->userWithRole('finance', 'finance-ap-branch@example.test', [
            'company_branch_id' => $branchA->id,
        ]);

        [$ownPurchaseOrder] = $this->receivedPurchaseOrder(branch: $branchA);
        [$otherPurchaseOrder] = $this->receivedPurchaseOrder(branch: $branchB);
        $ownInvoice = ApInvoice::issueFromPurchaseOrder($ownPurchaseOrder, $finance);
        $otherInvoice = ApInvoice::issueFromPurchaseOrder($otherPurchaseOrder, $finance);

        $this->actingAs($finance)
            ->get(route('ap-invoices.index'))
            ->assertOk()
            ->assertSee($ownInvoice->invoice_number)
            ->assertDontSee($otherInvoice->invoice_number);
    }

    public function test_ap_invoice_and_supplier_payment_numbers_include_branch_code(): void
    {
        [$branchA] = $this->twoCompanyBranches();
        $finance = $this->userWithRole('finance', 'finance-ap-numbering@example.test', [
            'company_branch_id' => $branchA->id,
        ]);
        [$purchaseOrder] = $this->receivedPurchaseOrder(branch: $branchA);

        $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, $finance);
        $payment = SupplierPayment::payForInvoice($invoice, [
            'payment_date' => now()->toDateString(),
            'payment_method' => SupplierPayment::METHOD_TRANSFER,
            'amount' => 10000,
        ], $finance);
        $branchCode = strtoupper(substr($branchA->code, 0, 3));

        $this->assertStringContainsString("-{$branchCode}-", $invoice->invoice_number);
        $this->assertStringContainsString("-{$branchCode}-", $payment->payment_number);
    }

    public function test_branch_finance_cannot_issue_ap_invoice_for_other_branch_po(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $finance = $this->userWithRole('finance', 'finance-ap-other-po@example.test', [
            'company_branch_id' => $branchA->id,
        ]);
        [$otherPurchaseOrder] = $this->receivedPurchaseOrder(branch: $branchB);

        $this->actingAs($finance)
            ->post(route('ap-invoices.store'), ['purchase_order_id' => $otherPurchaseOrder->id])
            ->assertNotFound();

        $this->assertDatabaseCount('ap_invoices', 0);
    }

    public function test_branch_finance_cannot_pay_other_branch_ap_invoice(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $finance = $this->userWithRole('finance', 'finance-ap-other-payment@example.test', [
            'company_branch_id' => $branchA->id,
        ]);
        [$otherPurchaseOrder] = $this->receivedPurchaseOrder(branch: $branchB);
        $otherInvoice = ApInvoice::issueFromPurchaseOrder($otherPurchaseOrder, $finance);

        $this->actingAs($finance)
            ->post(route('supplier-payments.store'), [
                'ap_invoice_id' => $otherInvoice->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => SupplierPayment::METHOD_TRANSFER,
                'amount' => 10000,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('supplier_payments', 0);
    }

    public function test_ap_auto_journals_flow_into_trial_balance(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ap-trial@example.test');
        [$purchaseOrder] = $this->receivedPurchaseOrder();
        $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, $finance);
        SupplierPayment::payForInvoice($invoice, [
            'payment_date' => now()->toDateString(),
            'payment_method' => SupplierPayment::METHOD_TRANSFER,
            'amount' => 60000,
        ], $finance);

        $this->actingAs($finance)
            ->get(route('trial-balance.index', [
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Neraca Saldo')
            ->assertSee('Persediaan Barang Dagang')
            ->assertSee('Kas dan Bank');

        $this->assertDatabaseHas('chart_accounts', [
            'code' => '2101',
            'name' => 'Hutang Usaha',
            'account_type' => ChartAccount::TYPE_LIABILITY,
        ]);
    }

    private function receivedPurchaseOrder(
        string $status = PurchaseOrder::STATUS_RECEIVED,
        int $receivedQuantity = 3,
        ?CompanyBranch $branch = null,
    ): array {
        $creator = $this->userWithRole('admin', 'po-creator-' . uniqid() . '@example.test', [
            'company_branch_id' => $branch?->id,
        ]);
        $supplier = Supplier::create([
            'name' => 'Pemasok AP',
            'phone' => '0812' . random_int(10000000, 99999999),
            'category' => Supplier::CATEGORY_ALL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk AP',
            'category' => 'Sayur',
            'price' => 25000,
            'base_price' => 20000,
            'is_active' => true,
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'POAP' . random_int(10000, 99999),
            'supplier_id' => $supplier->id,
            'company_branch_id' => $branch?->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->toDateString(),
            'received_date' => $status === PurchaseOrder::STATUS_RECEIVED ? now()->toDateString() : null,
            'status' => $status,
            'subtotal' => 60000,
            'total' => 60000,
            'created_by' => $creator->id,
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'received_quantity' => $receivedQuantity,
            'price' => 20000,
            'subtotal' => 60000,
        ]);

        return [$purchaseOrder, $supplier];
    }

    private function twoCompanyBranches(): array
    {
        $company = CompanyProfile::create([
            'code' => 'KMG',
            'display_name' => 'Kurmigo Test',
            'legal_name' => 'PT Kurmigo Test',
            'is_active' => true,
        ]);

        $branchA = CompanyBranch::create([
            'company_profile_id' => $company->id,
            'name' => 'Cabang A',
            'code' => 'CBA',
            'is_invoice_default' => true,
            'is_active' => true,
        ]);

        $branchB = CompanyBranch::create([
            'company_profile_id' => $company->id,
            'name' => 'Cabang B',
            'code' => 'CBB',
            'is_active' => true,
        ]);

        return [$branchA, $branchB];
    }

    private function userWithRole(string $role, string $email, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['email' => $email], $attributes));
        $user->assignRole($role);

        return $user;
    }
}
