<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\AccountingPostingService;

class ArInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'user_id',
        'customer_id',
        'company_branch_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'packing_amount',
        'ppn_amount',
        'tax_base_amount',
        'tax_rate',
        'tax_transaction_code',
        'tax_status',
        'tax_invoice_number',
        'tax_invoice_date',
        'tax_exported_at',
        'tax_approved_at',
        'tax_error_message',
        'total_amount',
        'paid_amount',
        'credit_note_amount',
        'outstanding_amount',
        'notes',
        'issued_by',
        'issued_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'integer',
        'discount_amount' => 'integer',
        'shipping_amount' => 'integer',
        'packing_amount' => 'integer',
        'ppn_amount' => 'integer',
        'tax_base_amount' => 'integer',
        'tax_rate' => 'decimal:2',
        'tax_invoice_date' => 'date',
        'tax_exported_at' => 'datetime',
        'tax_approved_at' => 'datetime',
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
        'credit_note_amount' => 'integer',
        'outstanding_amount' => 'integer',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_VOID = 'void';

    public const TAX_NOT_REQUIRED = 'not_required';
    public const TAX_DRAFT = 'draft';
    public const TAX_READY = 'ready';
    public const TAX_EXPORTED = 'exported';
    public const TAX_APPROVED = 'approved';
    public const TAX_REJECTED = 'rejected';

    public const TAX_STATUS_LIST = [
        self::TAX_NOT_REQUIRED => 'Tidak Wajib',
        self::TAX_DRAFT => 'Draft',
        self::TAX_READY => 'Siap Coretax',
        self::TAX_EXPORTED => 'Exported',
        self::TAX_APPROVED => 'Approved',
        self::TAX_REJECTED => 'Rejected',
    ];

    public const STATUS_LIST = [
        self::STATUS_ISSUED => 'Terbit',
        self::STATUS_PARTIALLY_PAID => 'Bayar Sebagian',
        self::STATUS_PAID => 'Lunas',
        self::STATUS_OVERDUE => 'Jatuh Tempo',
        self::STATUS_VOID => 'Void',
    ];

    public const STATUS_BADGES = [
        self::STATUS_ISSUED => 'info',
        self::STATUS_PARTIALLY_PAID => 'warning',
        self::STATUS_PAID => 'success',
        self::STATUS_OVERDUE => 'danger',
        self::STATUS_VOID => 'secondary',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ArInvoiceItem::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(CustomerPaymentAllocation::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(ArCreditNote::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }

    public function getTaxStatusLabelAttribute(): string
    {
        return self::TAX_STATUS_LIST[$this->tax_status] ?? str($this->tax_status)->headline()->toString();
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== self::STATUS_PAID
            && $this->status !== self::STATUS_VOID
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function refreshPaymentStatus(): void
    {
        if ($this->status === self::STATUS_VOID) {
            return;
        }

        $outstanding = max(0, (int) $this->total_amount - (int) $this->paid_amount - (int) $this->credit_note_amount);
        $status = self::STATUS_ISSUED;

        if ($outstanding <= 0) {
            $status = self::STATUS_PAID;
        } elseif ($this->paid_amount > 0) {
            $status = self::STATUS_PARTIALLY_PAID;
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $status = self::STATUS_OVERDUE;
        }

        $this->forceFill([
            'outstanding_amount' => $outstanding,
            'status' => $status,
        ])->save();
    }

    public static function nextInvoiceNumber(?CompanyBranch $branch = null): string
    {
        $company = CompanyProfile::defaultProfile();
        $companyCode = $company?->document_code ?: 'DMS';
        $branchCode = $branch?->document_code ?: $branch?->code ?: 'MAIN';
        $date = now()->format('Ymd');
        $prefix = 'INV-' . strtoupper(substr($companyCode, 0, 3)) . '-' . strtoupper(substr($branchCode, 0, 3)) . '-' . $date;
        $last = self::where('invoice_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $sequence = $last ? ((int) substr($last->invoice_number, -4)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function issueFromOrder(Order $order, ?User $issuer = null): self
    {
        if ($order->arInvoice) {
            return $order->arInvoice;
        }

        if (!$order->isInvoiceableForAr()) {
            throw new \InvalidArgumentException('Order belum memenuhi syarat untuk dibuat AR Invoice.');
        }

        $order->loadMissing('items.product', 'user.customer', 'companyBranch');
        $customer = $order->user?->customer;
        $invoiceDate = now()->toDateString();
        $dueDate = self::resolveDueDate($customer);

        return \DB::transaction(function () use ($order, $issuer, $customer, $invoiceDate, $dueDate) {
            $invoice = self::create([
                'invoice_number' => self::nextInvoiceNumber($order->companyBranch),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'customer_id' => $customer?->id,
                'company_branch_id' => $order->company_branch_id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'status' => self::STATUS_ISSUED,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->delivery_fee,
                'packing_amount' => $order->packing_fee,
                'ppn_amount' => $order->ppn_amount,
                'tax_base_amount' => max(0, (int) ($order->grand_total ?: $order->total) - (int) $order->ppn_amount),
                'tax_rate' => (int) $order->ppn_amount > 0 ? 11 : 0,
                'tax_transaction_code' => '01',
                'tax_status' => (int) $order->ppn_amount > 0 ? self::TAX_DRAFT : self::TAX_NOT_REQUIRED,
                'total_amount' => $order->grand_total ?: $order->total,
                'paid_amount' => 0,
                'credit_note_amount' => 0,
                'outstanding_amount' => $order->grand_total ?: $order->total,
                'notes' => 'Dibuat dari order ' . $order->order_number,
                'issued_by' => $issuer?->id,
                'issued_at' => now(),
            ]);

            foreach ($order->items as $item) {
                $invoice->items()->create([
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'description' => $item->product_name ?: $item->product?->name ?: 'Item Order',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price,
                    'discount_amount' => $item->discount,
                    'line_total' => $item->subtotal,
                ]);
            }

            ActivityLog::record('ar_invoices', 'issued', 'AR Invoice diterbitkan', $invoice, [
                'invoice_number' => $invoice->invoice_number,
                'order_number' => $order->order_number,
                'total_amount' => $invoice->total_amount,
            ]);

            app(AccountingPostingService::class)->postArInvoice($invoice, $issuer);

            return $invoice;
        });
    }

    public function voidInvoice(string $reason, ?User $voidedBy = null): self
    {
        if ($this->status === self::STATUS_VOID) {
            throw new \InvalidArgumentException('Invoice ini sudah void.');
        }

        $this->loadMissing(['paymentAllocations.customerPayment', 'creditNotes']);
        $hasActivePayment = $this->paymentAllocations
            ->contains(fn (CustomerPaymentAllocation $allocation) => $allocation->customerPayment?->status !== CustomerPayment::STATUS_VOID);

        if ($hasActivePayment) {
            throw new \InvalidArgumentException('Void pembayaran customer terlebih dahulu sebelum void invoice.');
        }

        if ($this->creditNotes->contains(fn (ArCreditNote $creditNote) => $creditNote->status !== ArCreditNote::STATUS_VOID)) {
            throw new \InvalidArgumentException('Void credit note AR terlebih dahulu sebelum void invoice.');
        }

        return \DB::transaction(function () use ($reason, $voidedBy) {
            $this->forceFill([
                'status' => self::STATUS_VOID,
                'paid_amount' => 0,
                'outstanding_amount' => 0,
                'voided_by' => $voidedBy?->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();

            app(AccountingPostingService::class)->reverseSourcePosting(self::class, $this->id, $reason, $voidedBy);

            ActivityLog::record('ar_invoices', 'voided', 'AR Invoice di-void', $this, [
                'invoice_number' => $this->invoice_number,
                'void_reason' => $reason,
            ]);

            return $this;
        });
    }

    private static function resolveDueDate(?Customer $customer): string
    {
        if (!$customer || !$customer->usesCreditTerm()) {
            return now()->toDateString();
        }

        return now()->addDays(14)->toDateString();
    }
}
