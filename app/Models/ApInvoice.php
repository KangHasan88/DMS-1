<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPostingService;

class ApInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'purchase_order_id',
        'supplier_id',
        'company_branch_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'ppn_amount',
        'tax_base_amount',
        'tax_rate',
        'tax_status',
        'supplier_tax_invoice_number',
        'supplier_tax_invoice_date',
        'tax_exported_at',
        'tax_approved_at',
        'tax_error_message',
        'total_amount',
        'paid_amount',
        'debit_note_amount',
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
        'ppn_amount' => 'integer',
        'tax_base_amount' => 'integer',
        'tax_rate' => 'decimal:2',
        'supplier_tax_invoice_date' => 'date',
        'tax_exported_at' => 'datetime',
        'tax_approved_at' => 'datetime',
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
        'debit_note_amount' => 'integer',
        'outstanding_amount' => 'integer',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_VOID = 'void';

    public const TAX_NOT_RECEIVED = 'not_received';
    public const TAX_DRAFT = 'draft';
    public const TAX_CLAIMABLE = 'claimable';
    public const TAX_EXPORTED = 'exported';
    public const TAX_APPROVED = 'approved';
    public const TAX_REJECTED = 'rejected';
    public const TAX_NOT_CREDITABLE = 'not_creditable';

    public const TAX_STATUS_LIST = [
        self::TAX_NOT_RECEIVED => 'Belum Diterima',
        self::TAX_DRAFT => 'Draft',
        self::TAX_CLAIMABLE => 'Dapat Dikreditkan',
        self::TAX_EXPORTED => 'Exported',
        self::TAX_APPROVED => 'Approved',
        self::TAX_REJECTED => 'Rejected',
        self::TAX_NOT_CREDITABLE => 'Tidak Dikreditkan',
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(ApInvoiceItem::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class);
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(ApDebitNote::class);
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
        return !in_array($this->status, [self::STATUS_PAID, self::STATUS_VOID], true)
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function refreshPaymentStatus(): void
    {
        if ($this->status === self::STATUS_VOID) {
            return;
        }

        $outstanding = max(0, (int) $this->total_amount - (int) $this->paid_amount - (int) $this->debit_note_amount);
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
        $prefix = 'AP-' . strtoupper(substr($companyCode, 0, 3)) . '-' . strtoupper(substr($branchCode, 0, 3)) . '-' . $date;
        $last = self::where('invoice_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $sequence = $last ? ((int) substr($last->invoice_number, -4)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function issueFromPurchaseOrder(PurchaseOrder $purchaseOrder, ?User $issuer = null, array $options = []): self
    {
        if ($purchaseOrder->apInvoice) {
            return $purchaseOrder->apInvoice;
        }

        if (!$purchaseOrder->isInvoiceableForAp()) {
            throw new \InvalidArgumentException('PO belum memenuhi syarat untuk dibuat AP Invoice.');
        }

        $purchaseOrder->loadMissing('items.product', 'supplier', 'companyBranch');
        $invoiceDate = now()->toDateString();
        $dueDate = now()->addDays(14)->toDateString();
        $itemPrices = collect($options['item_prices'] ?? []);
        $invoiceLines = $purchaseOrder->items
            ->map(function ($item) use ($itemPrices) {
                $quantity = (int) $item->received_quantity;
                $unitPrice = (int) ($itemPrices->get($item->id) ?? $item->price);

                return [
                    'item' => $item,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $quantity * $unitPrice,
                ];
            })
            ->filter(fn (array $line) => $line['quantity'] > 0)
            ->values();
        $invoiceSubtotal = (int) $invoiceLines->sum('line_total');
        $taxRate = (float) ($options['tax_rate'] ?? 0);
        $taxAmount = $taxRate > 0 ? (int) round($invoiceSubtotal * ($taxRate / 100)) : 0;
        $invoiceTotal = $invoiceSubtotal + $taxAmount;
        $taxStatus = filled($options['supplier_tax_invoice_number'] ?? null)
            ? self::TAX_CLAIMABLE
            : self::TAX_NOT_RECEIVED;

        if ($invoiceSubtotal <= 0) {
            throw new \InvalidArgumentException('Tidak ada quantity diterima yang bisa dibuat AP Invoice.');
        }

        return DB::transaction(function () use ($purchaseOrder, $issuer, $invoiceDate, $dueDate, $invoiceLines, $invoiceSubtotal, $taxRate, $taxAmount, $invoiceTotal, $taxStatus, $options) {
            $invoice = self::create([
                'invoice_number' => self::nextInvoiceNumber($purchaseOrder->companyBranch),
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'company_branch_id' => $purchaseOrder->company_branch_id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'status' => self::STATUS_ISSUED,
                'subtotal' => $invoiceSubtotal,
                'ppn_amount' => $taxAmount,
                'tax_base_amount' => $invoiceSubtotal,
                'tax_rate' => $taxRate,
                'tax_status' => $taxStatus,
                'supplier_tax_invoice_number' => $options['supplier_tax_invoice_number'] ?? null,
                'supplier_tax_invoice_date' => $options['supplier_tax_invoice_date'] ?? null,
                'total_amount' => $invoiceTotal,
                'paid_amount' => 0,
                'debit_note_amount' => 0,
                'outstanding_amount' => $invoiceTotal,
                'notes' => $options['notes'] ?? 'Dibuat dari PO ' . $purchaseOrder->po_number,
                'issued_by' => $issuer?->id,
                'issued_at' => now(),
            ]);

            foreach ($invoiceLines as $line) {
                $item = $line['item'];

                $invoice->items()->create([
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'description' => $item->product?->name ?? 'Item PO',
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);
            }

            ActivityLog::record('ap_invoices', 'issued', 'AP Invoice diterbitkan', $invoice, [
                'invoice_number' => $invoice->invoice_number,
                'po_number' => $purchaseOrder->po_number,
                'total_amount' => $invoice->total_amount,
                'tax_amount' => $invoice->ppn_amount,
                'variance_note' => $options['variance_note'] ?? null,
            ]);

            app(AccountingPostingService::class)->postApInvoice($invoice, $issuer);

            return $invoice;
        });
    }

    public function voidInvoice(string $reason, ?User $voidedBy = null): self
    {
        if ($this->status === self::STATUS_VOID) {
            throw new \InvalidArgumentException('Invoice AP ini sudah void.');
        }

        $this->loadMissing(['paymentAllocations.supplierPayment', 'debitNotes']);
        $hasActivePayment = $this->paymentAllocations
            ->contains(fn (SupplierPaymentAllocation $allocation) => $allocation->supplierPayment?->status !== SupplierPayment::STATUS_VOID);

        if ($hasActivePayment) {
            throw new \InvalidArgumentException('Void pembayaran supplier terlebih dahulu sebelum void AP Invoice.');
        }

        if ($this->debitNotes->contains(fn (ApDebitNote $debitNote) => $debitNote->status !== ApDebitNote::STATUS_VOID)) {
            throw new \InvalidArgumentException('Void debit note AP terlebih dahulu sebelum void AP Invoice.');
        }

        return DB::transaction(function () use ($reason, $voidedBy) {
            $this->forceFill([
                'status' => self::STATUS_VOID,
                'paid_amount' => 0,
                'outstanding_amount' => 0,
                'voided_by' => $voidedBy?->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();

            app(AccountingPostingService::class)->reverseSourcePosting(self::class, $this->id, $reason, $voidedBy);

            ActivityLog::record('ap_invoices', 'voided', 'AP Invoice di-void', $this, [
                'invoice_number' => $this->invoice_number,
                'void_reason' => $reason,
            ]);

            return $this;
        });
    }

    public function scopeForUserBranch($query, ?User $user = null)
    {
        $branchScopeId = ($user ?? auth()->user())?->scopedCompanyBranchId();

        return $branchScopeId
            ? $query->where('company_branch_id', $branchScopeId)
            : $query;
    }
}
