<?php

namespace App\Models;

use App\Services\AccountingPostingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ArCreditNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_number',
        'ar_invoice_id',
        'customer_id',
        'user_id',
        'company_branch_id',
        'note_date',
        'reason_type',
        'amount',
        'reference_number',
        'notes',
        'status',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'note_date' => 'date',
        'amount' => 'integer',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    public const REASON_SALES_RETURN = 'sales_return';
    public const REASON_PRICE_ADJUSTMENT = 'price_adjustment';
    public const REASON_DISCOUNT = 'discount';
    public const REASON_DAMAGED_GOODS = 'damaged_goods';
    public const REASON_OTHER = 'other';

    public const REASON_LIST = [
        self::REASON_SALES_RETURN => 'Retur Penjualan',
        self::REASON_PRICE_ADJUSTMENT => 'Koreksi Harga',
        self::REASON_DISCOUNT => 'Diskon Customer',
        self::REASON_DAMAGED_GOODS => 'Klaim Barang Rusak',
        self::REASON_OTHER => 'Lainnya',
    ];

    public const STATUS_LIST = [
        self::STATUS_POSTED => 'Posted',
        self::STATUS_VOID => 'Void',
    ];

    public const STATUS_BADGES = [
        self::STATUS_POSTED => 'success',
        self::STATUS_VOID => 'secondary',
    ];

    public function arInvoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASON_LIST[$this->reason_type] ?? str($this->reason_type)->headline()->toString();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }

    public static function nextNoteNumber(?CompanyBranch $branch = null): string
    {
        $company = CompanyProfile::defaultProfile();
        $companyCode = $company?->document_code ?: 'DMS';
        $branchCode = $branch?->document_code ?: $branch?->code ?: 'MAIN';
        $date = now()->format('Ymd');
        $prefix = 'ARCN-' . strtoupper(substr($companyCode, 0, 3)) . '-' . strtoupper(substr($branchCode, 0, 3)) . '-' . $date;
        $last = self::where('note_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $sequence = $last ? ((int) substr($last->note_number, -4)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function postForInvoice(ArInvoice $invoice, array $data, ?User $postedBy = null): self
    {
        $invoice->loadMissing(['customer', 'customerUser', 'companyBranch']);
        $amount = (int) $data['amount'];

        if ($invoice->status === ArInvoice::STATUS_VOID) {
            throw new \InvalidArgumentException('Credit note tidak bisa dibuat untuk AR Invoice void.');
        }

        if ($amount > (int) $invoice->outstanding_amount) {
            throw new \InvalidArgumentException('Nominal credit note melebihi outstanding AR Invoice.');
        }

        return DB::transaction(function () use ($invoice, $data, $postedBy, $amount) {
            $creditNote = self::create([
                'note_number' => self::nextNoteNumber($invoice->companyBranch),
                'ar_invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'user_id' => $invoice->user_id,
                'company_branch_id' => $invoice->company_branch_id,
                'note_date' => $data['note_date'],
                'reason_type' => $data['reason_type'],
                'amount' => $amount,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => self::STATUS_POSTED,
                'posted_by' => $postedBy?->id,
                'posted_at' => now(),
            ]);

            $invoice->forceFill([
                'credit_note_amount' => (int) $invoice->credit_note_amount + $amount,
            ]);
            $invoice->refreshPaymentStatus();

            ActivityLog::record('ar_credit_notes', 'posted', 'Credit note AR diposting', $creditNote, [
                'note_number' => $creditNote->note_number,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $amount,
            ]);

            app(AccountingPostingService::class)->postArCreditNote($creditNote, $postedBy);

            return $creditNote;
        });
    }

    public function voidNote(string $reason, ?User $voidedBy = null): self
    {
        if ($this->status === self::STATUS_VOID) {
            throw new \InvalidArgumentException('Credit note AR ini sudah void.');
        }

        $this->loadMissing(['arInvoice', 'companyBranch']);

        return DB::transaction(function () use ($reason, $voidedBy) {
            $invoice = $this->arInvoice;
            $invoice->forceFill([
                'credit_note_amount' => max(0, (int) $invoice->credit_note_amount - (int) $this->amount),
            ]);
            $invoice->refreshPaymentStatus();

            $this->forceFill([
                'status' => self::STATUS_VOID,
                'voided_by' => $voidedBy?->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();

            app(AccountingPostingService::class)->reverseSourcePosting(self::class, $this->id, $reason, $voidedBy);

            ActivityLog::record('ar_credit_notes', 'voided', 'Credit note AR di-void', $this, [
                'note_number' => $this->note_number,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $this->amount,
                'reason' => $reason,
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
