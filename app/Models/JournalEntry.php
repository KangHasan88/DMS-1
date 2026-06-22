<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? str($this->status)->headline()->toString();
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
