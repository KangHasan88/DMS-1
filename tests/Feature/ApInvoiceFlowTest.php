<?php

namespace Tests\Feature;

use App\Models\ApInvoice;
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

    private function receivedPurchaseOrder(
        string $status = PurchaseOrder::STATUS_RECEIVED,
        int $receivedQuantity = 3
    ): array {
        $creator = $this->userWithRole('admin', 'po-creator-' . uniqid() . '@example.test');
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

    private function userWithRole(string $role, string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole($role);

        return $user;
    }
}
