<?php

namespace Tests\Feature;

use App\Models\ArInvoice;
use App\Models\ArCreditNote;
use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_finance_can_issue_ar_invoice_from_delivered_order(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ar@example.test');
        [$order, $customer] = $this->deliveredOrder();

        $this->actingAs($finance)
            ->post(route('ar-invoices.store'), ['order_id' => $order->id])
            ->assertRedirect();

        $invoice = ArInvoice::with('items')->firstOrFail();

        $this->assertSame($order->id, $invoice->order_id);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame(50000, $invoice->total_amount);
        $this->assertSame(50000, $invoice->outstanding_amount);
        $this->assertSame(ArInvoice::STATUS_ISSUED, $invoice->status);
        $this->assertCount(1, $invoice->items);
        $this->assertSame('Produk AR', $invoice->items->first()->description);

        $journal = JournalEntry::with('lines.account')
            ->where('source_type', ArInvoice::class)
            ->where('source_id', $invoice->id)
            ->firstOrFail();

        $this->assertSame(50000, $journal->debit_total);
        $this->assertSame(50000, $journal->credit_total);
        $this->assertStringContainsString('-' . strtoupper(substr($order->companyBranch->code, 0, 3)) . '-', $journal->journal_number);
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '1102' && $line->debit_amount === 50000));
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '4101' && $line->credit_amount === 50000));

        $this->actingAs($finance)
            ->get(route('ar-invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('Produk AR');
    }

    public function test_ar_invoice_cannot_be_issued_before_order_is_delivered(): void
    {
        $finance = $this->userWithRole('finance', 'finance-not-ready@example.test');
        [$order] = $this->deliveredOrder(Order::STATUS_READY);

        $this->actingAs($finance)
            ->from(route('ar-invoices.index'))
            ->post(route('ar-invoices.store'), ['order_id' => $order->id])
            ->assertRedirect(route('ar-invoices.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ar_invoices', 0);
    }

    public function test_ar_invoice_menu_is_available_from_sidebar(): void
    {
        $finance = $this->userWithRole('finance', 'finance-sidebar@example.test');

        $this->actingAs($finance)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Invoice AR')
            ->assertSee('Pembayaran Customer');
    }

    public function test_finance_can_record_partial_customer_payment_for_ar_invoice(): void
    {
        $finance = $this->userWithRole('finance', 'finance-payment-partial@example.test');
        [$order] = $this->deliveredOrder();
        $invoice = ArInvoice::issueFromOrder($order, $finance);

        $this->actingAs($finance)
            ->post(route('customer-payments.store'), [
                'ar_invoice_id' => $invoice->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => CustomerPayment::METHOD_TRANSFER,
                'amount' => 20000,
                'reference_number' => 'TRF-001',
                'notes' => 'Bayar sebagian',
            ])
            ->assertRedirect(route('ar-invoices.show', $invoice->fresh()));

        $invoice->refresh();
        $payment = CustomerPayment::with('allocations')->firstOrFail();

        $this->assertSame(20000, $payment->amount);
        $this->assertSame(0, $payment->unallocated_amount);
        $this->assertCount(1, $payment->allocations);
        $this->assertSame(20000, $invoice->paid_amount);
        $this->assertSame(30000, $invoice->outstanding_amount);
        $this->assertSame(ArInvoice::STATUS_PARTIALLY_PAID, $invoice->status);

        $journal = JournalEntry::with('lines.account')
            ->where('source_type', CustomerPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();

        $this->assertSame(20000, $journal->debit_total);
        $this->assertSame(20000, $journal->credit_total);
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '1110' && $line->debit_amount === 20000));
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '1102' && $line->credit_amount === 20000));
    }

    public function test_finance_can_post_ar_credit_note_and_reduce_invoice_outstanding(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ar-credit-note@example.test');
        [$order] = $this->deliveredOrder();
        $invoice = ArInvoice::issueFromOrder($order, $finance);

        $this->actingAs($finance)
            ->post(route('ar-credit-notes.store'), [
                'ar_invoice_id' => $invoice->id,
                'note_date' => now()->toDateString(),
                'reason_type' => ArCreditNote::REASON_SALES_RETURN,
                'amount' => 12000,
                'reference_number' => 'RTR-CUST-001',
                'notes' => 'Retur barang rusak',
            ])
            ->assertRedirect(route('ar-invoices.show', $invoice->fresh()));

        $invoice->refresh();
        $creditNote = ArCreditNote::firstOrFail();

        $this->assertSame(12000, $creditNote->amount);
        $this->assertSame(ArCreditNote::STATUS_POSTED, $creditNote->status);
        $this->assertSame(12000, $invoice->credit_note_amount);
        $this->assertSame(38000, $invoice->outstanding_amount);
        $this->assertSame(ArInvoice::STATUS_ISSUED, $invoice->status);

        $journal = JournalEntry::with('lines.account')
            ->where('source_type', ArCreditNote::class)
            ->where('source_id', $creditNote->id)
            ->firstOrFail();

        $this->assertSame(12000, $journal->debit_total);
        $this->assertSame(12000, $journal->credit_total);
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '4102' && $line->debit_amount === 12000));
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '1102' && $line->credit_amount === 12000));

        $this->actingAs($finance)
            ->get(route('ar-credit-notes.index'))
            ->assertOk()
            ->assertSee($creditNote->note_number)
            ->assertSee('Retur Penjualan');
    }

    public function test_ar_credit_note_can_be_voided_and_restores_invoice_outstanding(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ar-credit-note-void@example.test');
        [$order] = $this->deliveredOrder();
        $invoice = ArInvoice::issueFromOrder($order, $finance);
        $creditNote = ArCreditNote::postForInvoice($invoice, [
            'note_date' => now()->toDateString(),
            'reason_type' => ArCreditNote::REASON_PRICE_ADJUSTMENT,
            'amount' => 10000,
        ], $finance);

        $this->actingAs($finance)
            ->post(route('ar-credit-notes.void', $creditNote), [
                'void_reason' => 'Salah koreksi',
            ])
            ->assertRedirect(route('ar-invoices.show', $invoice));

        $invoice->refresh();
        $creditNote->refresh();
        $originalJournal = JournalEntry::where('source_type', ArCreditNote::class)
            ->where('source_id', $creditNote->id)
            ->firstOrFail();
        $reversal = JournalEntry::where('source_type', JournalEntry::class)
            ->where('source_id', $originalJournal->id)
            ->firstOrFail();

        $this->assertSame(ArCreditNote::STATUS_VOID, $creditNote->status);
        $this->assertSame('Salah koreksi', $creditNote->void_reason);
        $this->assertSame(0, $invoice->credit_note_amount);
        $this->assertSame(50000, $invoice->outstanding_amount);
        $this->assertSame(JournalEntry::STATUS_VOID, $originalJournal->fresh()->status);
        $this->assertSame(10000, $reversal->debit_total);
        $this->assertSame(10000, $reversal->credit_total);
    }

    public function test_full_customer_payment_marks_ar_invoice_paid(): void
    {
        $finance = $this->userWithRole('finance', 'finance-payment-full@example.test');
        [$order] = $this->deliveredOrder();
        $invoice = ArInvoice::issueFromOrder($order, $finance);

        $this->actingAs($finance)
            ->post(route('customer-payments.store'), [
                'ar_invoice_id' => $invoice->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => CustomerPayment::METHOD_CASH,
                'amount' => 50000,
            ])
            ->assertRedirect(route('ar-invoices.show', $invoice->fresh()));

        $invoice->refresh();

        $this->assertSame(50000, $invoice->paid_amount);
        $this->assertSame(0, $invoice->outstanding_amount);
        $this->assertSame(ArInvoice::STATUS_PAID, $invoice->status);
    }

    public function test_customer_payment_cannot_exceed_invoice_outstanding(): void
    {
        $finance = $this->userWithRole('finance', 'finance-payment-over@example.test');
        [$order] = $this->deliveredOrder();
        $invoice = ArInvoice::issueFromOrder($order, $finance);

        $this->actingAs($finance)
            ->from(route('ar-invoices.show', $invoice))
            ->post(route('customer-payments.store'), [
                'ar_invoice_id' => $invoice->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => CustomerPayment::METHOD_TRANSFER,
                'amount' => 60000,
            ])
            ->assertRedirect(route('ar-invoices.show', $invoice))
            ->assertSessionHas('error');

        $invoice->refresh();

        $this->assertDatabaseCount('customer_payments', 0);
        $this->assertSame(0, $invoice->paid_amount);
        $this->assertSame(50000, $invoice->outstanding_amount);
    }

    public function test_finance_can_void_customer_payment_and_restore_ar_invoice_outstanding(): void
    {
        $finance = $this->userWithRole('finance', 'finance-payment-void@example.test');
        [$order] = $this->deliveredOrder();
        $invoice = ArInvoice::issueFromOrder($order, $finance);
        $payment = CustomerPayment::receiveForInvoice($invoice, [
            'payment_date' => now()->toDateString(),
            'payment_method' => CustomerPayment::METHOD_TRANSFER,
            'amount' => 20000,
        ], $finance);

        $this->actingAs($finance)
            ->post(route('customer-payments.void', $payment), [
                'void_reason' => 'Salah nominal',
            ])
            ->assertRedirect(route('ar-invoices.show', $invoice));

        $invoice->refresh();
        $payment->refresh();
        $originalJournal = JournalEntry::where('source_type', CustomerPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();
        $reversal = JournalEntry::where('source_type', JournalEntry::class)
            ->where('source_id', $originalJournal->id)
            ->firstOrFail();

        $this->assertSame(CustomerPayment::STATUS_VOID, $payment->status);
        $this->assertSame('Salah nominal', $payment->void_reason);
        $this->assertSame(0, $invoice->paid_amount);
        $this->assertSame(50000, $invoice->outstanding_amount);
        $this->assertSame(ArInvoice::STATUS_ISSUED, $invoice->status);
        $this->assertSame(JournalEntry::STATUS_VOID, $originalJournal->fresh()->status);
        $this->assertSame(20000, $reversal->debit_total);
        $this->assertSame(20000, $reversal->credit_total);

        $this->actingAs($finance)
            ->get(route('customer-payments.index', ['status' => CustomerPayment::STATUS_VOID]))
            ->assertOk()
            ->assertSee($payment->payment_number)
            ->assertSee('Void');
    }

    public function test_finance_can_void_unpaid_ar_invoice_with_reversal_entry(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ar-void@example.test');
        [$order] = $this->deliveredOrder();
        $invoice = ArInvoice::issueFromOrder($order, $finance);

        $this->actingAs($finance)
            ->post(route('ar-invoices.void', $invoice), [
                'void_reason' => 'Salah terbit invoice',
            ])
            ->assertRedirect(route('ar-invoices.show', $invoice));

        $invoice->refresh();
        $originalJournal = JournalEntry::where('source_type', ArInvoice::class)
            ->where('source_id', $invoice->id)
            ->firstOrFail();
        $reversal = JournalEntry::where('source_type', JournalEntry::class)
            ->where('source_id', $originalJournal->id)
            ->firstOrFail();

        $this->assertSame(ArInvoice::STATUS_VOID, $invoice->status);
        $this->assertSame(0, $invoice->paid_amount);
        $this->assertSame(0, $invoice->outstanding_amount);
        $this->assertSame('Salah terbit invoice', $invoice->void_reason);
        $this->assertSame(JournalEntry::STATUS_VOID, $originalJournal->fresh()->status);
        $this->assertSame(50000, $reversal->debit_total);
        $this->assertSame(50000, $reversal->credit_total);
    }

    public function test_ar_invoice_with_active_payment_must_void_payment_first(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ar-void-blocked@example.test');
        [$order] = $this->deliveredOrder();
        $invoice = ArInvoice::issueFromOrder($order, $finance);
        CustomerPayment::receiveForInvoice($invoice, [
            'payment_date' => now()->toDateString(),
            'payment_method' => CustomerPayment::METHOD_TRANSFER,
            'amount' => 20000,
        ], $finance);

        $this->actingAs($finance)
            ->from(route('ar-invoices.show', $invoice))
            ->post(route('ar-invoices.void', $invoice), [
                'void_reason' => 'Salah terbit invoice',
            ])
            ->assertRedirect(route('ar-invoices.show', $invoice))
            ->assertSessionHas('error');

        $this->assertSame(ArInvoice::STATUS_PARTIALLY_PAID, $invoice->fresh()->status);
    }

    public function test_ar_auto_journals_flow_into_trial_balance(): void
    {
        $finance = $this->userWithRole('finance', 'finance-ar-trial@example.test');
        [$order] = $this->deliveredOrder();
        $invoice = ArInvoice::issueFromOrder($order, $finance);
        CustomerPayment::receiveForInvoice($invoice, [
            'payment_date' => now()->toDateString(),
            'payment_method' => CustomerPayment::METHOD_TRANSFER,
            'amount' => 50000,
        ], $finance);

        $this->actingAs($finance)
            ->get(route('trial-balance.index', [
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Neraca Saldo')
            ->assertSee('Kas dan Bank')
            ->assertSee('Pendapatan Penjualan');

        $this->assertDatabaseHas('chart_accounts', [
            'code' => '1102',
            'name' => 'Piutang Usaha',
            'account_type' => ChartAccount::TYPE_ASSET,
        ]);
    }

    private function deliveredOrder(string $status = Order::STATUS_DELIVERED): array
    {
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = User::factory()->create(['name' => 'Customer AR']);
        $customerUser->assignRole('customer');
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer AR',
            'phone' => '0812' . random_int(10000000, 99999999),
            'email' => 'customer-ar@example.test',
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CREDIT,
            'credit_limit' => 1000000,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk AR',
            'category' => 'Sayur',
            'price' => 25000,
            'base_price' => 15000,
            'is_active' => true,
        ]);
        $order = Order::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'order_number' => 'KMGAR' . random_int(10000, 99999),
            'delivery_date' => now()->toDateString(),
            'delivery_time_slot' => '06:00-09:00',
            'address' => 'Jl. AR Test',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'subtotal' => 50000,
            'total' => 50000,
            'grand_total' => 50000,
            'status' => $status,
            'order_source' => Order::ORDER_SOURCE_ADMIN,
            'payment_method' => Order::PAYMENT_MANUAL,
            'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'discount_type' => Order::DISCOUNT_NONE,
            'discount_value' => 0,
            'discount_amount' => 0,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'include_ppn' => false,
            'ppn_rate' => 11,
            'ppn_amount' => 0,
            'delivered_at' => $status === Order::STATUS_DELIVERED ? now() : null,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 25000,
            'discount' => 0,
            'quantity' => 2,
            'subtotal' => 50000,
            'is_available' => true,
            'fulfillment_status' => OrderItem::FULFILLMENT_FULFILLED,
        ]);

        return [$order, $customer];
    }

    private function userWithRole(string $role, string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole($role);

        return $user;
    }
}
