<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPostingService;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'supplier_id',
        'company_branch_id',
        'payment_date',
        'payment_method',
        'reference_number',
        'amount',
        'unallocated_amount',
        'status',
        'notes',
        'paid_by',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'integer',
        'unallocated_amount' => 'integer',
        'voided_at' => 'datetime',
    ];

    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    public const STATUS_LIST = [
        self::STATUS_PAID => 'Dibayar',
        self::STATUS_VOID => 'Void',
    ];

    public const STATUS_BADGES = [
        self::STATUS_PAID => 'success',
        self::STATUS_VOID => 'secondary',
    ];

    public const METHOD_CASH = 'cash';
    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_GIRO = 'giro';
    public const METHOD_OTHER = 'other';

    public const METHOD_LIST = [
        self::METHOD_CASH => 'Tunai',
        self::METHOD_TRANSFER => 'Transfer',
        self::METHOD_GIRO => 'Giro',
        self::METHOD_OTHER => 'Lainnya',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHOD_LIST[$this->payment_method] ?? str($this->payment_method)->headline()->toString();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }

    public function getIsFullyAllocatedAttribute(): bool
    {
        return $this->status === self::STATUS_VOID || (int) $this->unallocated_amount <= 0;
    }

    public static function nextPaymentNumber(): string
    {
        $company = CompanyProfile::defaultProfile();
        $companyCode = $company?->document_code ?: 'DMS';
        $date = now()->format('Ymd');
        $prefix = 'SPAY-' . strtoupper(substr($companyCode, 0, 3)) . '-' . $date;
        $last = self::where('payment_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $sequence = $last ? ((int) substr($last->payment_number, -4)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function payForInvoice(ApInvoice $invoice, array $data, ?User $payer = null): self
    {
        $invoice->loadMissing('supplier');
        $amount = (int) $data['amount'];

        return DB::transaction(function () use ($invoice, $data, $payer, $amount) {
            $payment = self::create([
                'payment_number' => self::nextPaymentNumber(),
                'supplier_id' => $invoice->supplier_id,
                'company_branch_id' => $invoice->company_branch_id,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'amount' => $amount,
                'unallocated_amount' => $amount,
                'status' => self::STATUS_PAID,
                'notes' => $data['notes'] ?? null,
                'paid_by' => $payer?->id,
            ]);

            $payment->allocateToInvoice($invoice, $amount, $data['notes'] ?? null);

            ActivityLog::record('supplier_payments', 'paid', 'Pembayaran supplier dicatat', $payment, [
                'payment_number' => $payment->payment_number,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $amount,
            ]);

            app(AccountingPostingService::class)->postSupplierPayment($payment, $payer);

            return $payment;
        });
    }

    public function voidPayment(string $reason, ?User $voidedBy = null): self
    {
        if ($this->status === self::STATUS_VOID) {
            throw new \InvalidArgumentException('Pembayaran supplier ini sudah void.');
        }

        $this->loadMissing(['allocations.apInvoice', 'companyBranch']);

        return DB::transaction(function () use ($reason, $voidedBy) {
            foreach ($this->allocations as $allocation) {
                $invoice = $allocation->apInvoice;

                if (!$invoice) {
                    continue;
                }

                $invoice->forceFill([
                    'paid_amount' => max(0, (int) $invoice->paid_amount - (int) $allocation->amount),
                ]);
                $invoice->refreshPaymentStatus();
            }

            $this->forceFill([
                'status' => self::STATUS_VOID,
                'unallocated_amount' => 0,
                'voided_by' => $voidedBy?->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();

            app(AccountingPostingService::class)->reverseSourcePosting(self::class, $this->id, $reason, $voidedBy);

            ActivityLog::record('supplier_payments', 'voided', 'Pembayaran supplier di-void', $this, [
                'payment_number' => $this->payment_number,
                'amount' => $this->amount,
                'void_reason' => $reason,
            ]);

            return $this;
        });
    }

    public function allocateToInvoice(ApInvoice $invoice, int $amount, ?string $notes = null): SupplierPaymentAllocation
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal pembayaran harus lebih dari 0.');
        }

        if ($invoice->status === ApInvoice::STATUS_VOID) {
            throw new \InvalidArgumentException('Invoice void tidak bisa dibayar.');
        }

        if ((int) $this->supplier_id !== (int) $invoice->supplier_id) {
            throw new \InvalidArgumentException('Pembayaran dan invoice harus milik pemasok yang sama.');
        }

        if (
            $this->company_branch_id
            && $invoice->company_branch_id
            && (int) $this->company_branch_id !== (int) $invoice->company_branch_id
        ) {
            throw new \InvalidArgumentException('Pembayaran dan invoice harus berada pada cabang yang sama.');
        }

        if ($amount > (int) $this->unallocated_amount) {
            throw new \InvalidArgumentException('Nominal alokasi melebihi saldo pembayaran.');
        }

        if ($amount > (int) $invoice->outstanding_amount) {
            throw new \InvalidArgumentException('Nominal pembayaran melebihi outstanding invoice.');
        }

        return DB::transaction(function () use ($invoice, $amount, $notes) {
            $allocation = $this->allocations()->create([
                'ap_invoice_id' => $invoice->id,
                'amount' => $amount,
                'notes' => $notes,
            ]);

            $invoice->forceFill([
                'paid_amount' => (int) $invoice->paid_amount + $amount,
            ]);
            $invoice->refreshPaymentStatus();

            $this->forceFill([
                'unallocated_amount' => max(0, (int) $this->unallocated_amount - $amount),
            ])->save();

            ActivityLog::record('supplier_payments', 'allocated', 'Pembayaran dialokasikan ke AP Invoice', $allocation, [
                'payment_number' => $this->payment_number,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $amount,
            ]);

            return $allocation;
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
