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
        'total_amount',
        'paid_amount',
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
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
        'outstanding_amount' => 'integer',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_VOID = 'void';

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

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }

    public function getIsOverdueAttribute(): bool
    {
        return !in_array($this->status, [self::STATUS_PAID, self::STATUS_VOID], true)
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function refreshPaymentStatus(): void
    {
        $outstanding = max(0, (int) $this->total_amount - (int) $this->paid_amount);
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

    public static function nextInvoiceNumber(): string
    {
        $company = CompanyProfile::defaultProfile();
        $companyCode = $company?->document_code ?: 'DMS';
        $date = now()->format('Ymd');
        $prefix = 'AP-' . strtoupper(substr($companyCode, 0, 3)) . '-' . $date;
        $last = self::where('invoice_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $sequence = $last ? ((int) substr($last->invoice_number, -4)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function issueFromPurchaseOrder(PurchaseOrder $purchaseOrder, ?User $issuer = null): self
    {
        if ($purchaseOrder->apInvoice) {
            return $purchaseOrder->apInvoice;
        }

        if (!$purchaseOrder->isInvoiceableForAp()) {
            throw new \InvalidArgumentException('PO belum memenuhi syarat untuk dibuat AP Invoice.');
        }

        $purchaseOrder->loadMissing('items.product', 'supplier');
        $invoiceDate = now()->toDateString();
        $dueDate = now()->addDays(14)->toDateString();

        return DB::transaction(function () use ($purchaseOrder, $issuer, $invoiceDate, $dueDate) {
            $invoice = self::create([
                'invoice_number' => self::nextInvoiceNumber(),
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'company_branch_id' => $purchaseOrder->company_branch_id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'status' => self::STATUS_ISSUED,
                'subtotal' => $purchaseOrder->subtotal,
                'total_amount' => $purchaseOrder->total,
                'paid_amount' => 0,
                'outstanding_amount' => $purchaseOrder->total,
                'notes' => 'Dibuat dari PO ' . $purchaseOrder->po_number,
                'issued_by' => $issuer?->id,
                'issued_at' => now(),
            ]);

            foreach ($purchaseOrder->items as $item) {
                $invoice->items()->create([
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'description' => $item->product?->name ?? 'Item PO',
                    'quantity' => $item->received_quantity ?: $item->quantity,
                    'unit_price' => $item->price,
                    'line_total' => ($item->received_quantity ?: $item->quantity) * $item->price,
                ]);
            }

            ActivityLog::record('ap_invoices', 'issued', 'AP Invoice diterbitkan', $invoice, [
                'invoice_number' => $invoice->invoice_number,
                'po_number' => $purchaseOrder->po_number,
                'total_amount' => $invoice->total_amount,
            ]);

            app(AccountingPostingService::class)->postApInvoice($invoice, $issuer);

            return $invoice;
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
