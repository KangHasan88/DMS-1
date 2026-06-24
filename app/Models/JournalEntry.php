<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_number',
        'journal_date',
        'description',
        'company_branch_id',
        'status',
        'source_type',
        'source_id',
        'debit_total',
        'credit_total',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'debit_total' => 'integer',
        'credit_total' => 'integer',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    public const STATUS_LIST = [
        self::STATUS_POSTED => 'Posted',
        self::STATUS_VOID => 'Void',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function getSourceDocumentLabelAttribute(): string
    {
        return match ($this->source_type) {
            ArInvoice::class => 'Invoice AR',
            ArCreditNote::class => 'Credit Note AR',
            CustomerPayment::class => 'Pembayaran Customer',
            ApInvoice::class => 'Invoice AP',
            ApDebitNote::class => 'Debit Note AP',
            SupplierPayment::class => 'Pembayaran Supplier',
            self::class => 'Jurnal Reversal',
            null => 'Jurnal Manual',
            default => class_basename($this->source_type),
        };
    }

    public function getSourceDocumentNumberAttribute(): string
    {
        $source = $this->source;

        if (!$source) {
            return $this->source_type ? '-' : 'Manual';
        }

        return $source->invoice_number
            ?? $source->note_number
            ?? $source->payment_number
            ?? $source->journal_number
            ?? ('#' . $this->source_id);
    }

    public function getSourceDocumentUrlAttribute(): ?string
    {
        if (!$this->source_type || !$this->source_id) {
            return null;
        }

        return match ($this->source_type) {
            ArInvoice::class => route('ar-invoices.show', $this->source_id),
            ArCreditNote::class => route('ar-credit-notes.show', $this->source_id),
            CustomerPayment::class => route('customer-payments.show', $this->source_id),
            ApInvoice::class => route('ap-invoices.show', $this->source_id),
            ApDebitNote::class => route('ap-debit-notes.show', $this->source_id),
            SupplierPayment::class => route('supplier-payments.show', $this->source_id),
            self::class => route('journal-entries.show', $this->source_id),
            default => null,
        };
    }

    public static function nextJournalNumber(?CompanyBranch $branch = null): string
    {
        $company = CompanyProfile::defaultProfile();
        $companyCode = $company?->document_code ?: 'DMS';
        $branchCode = $branch?->document_code ?: $branch?->code ?: 'MAIN';
        $date = now()->format('Ymd');
        $prefix = 'JRN-' . strtoupper(substr($companyCode, 0, 3)) . '-' . strtoupper(substr($branchCode, 0, 3)) . '-' . $date;
        $last = self::where('journal_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $sequence = $last ? ((int) substr($last->journal_number, -4)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
