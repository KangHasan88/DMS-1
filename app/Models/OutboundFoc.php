<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundFoc extends Model
{
    use HasFactory;

    protected $fillable = [
        'foc_number',
        'company_branch_id',
        'customer_name',
        'customer_phone',
        'address',
        'foc_date',
        'reason',
        'reason_detail',
        'reference_order',
        'subtotal',
        'total',
        'notes',
        'created_by',
        'approval_request_id',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_note',
    ];

    protected $casts = [
        'foc_date' => 'date',
        'subtotal' => 'integer',
        'total' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // ===================== REASON CONSTANTS =====================
    
    const REASON_PROMOTION = 'promotion';
    const REASON_SAMPLE = 'sample';
    const REASON_SUPPORT = 'support';
    const REASON_COMPENSATION = 'compensation';
    const REASON_OTHER = 'other';

    const REASONS = [
        self::REASON_PROMOTION => 'Promosi',
        self::REASON_SAMPLE => 'Sample Produk',
        self::REASON_SUPPORT => 'Support Customer',
        self::REASON_COMPENSATION => 'Kompensasi',
        self::REASON_OTHER => 'Lainnya',
    ];

    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';

    const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING => 'Menunggu Approval',
        self::APPROVAL_APPROVED => 'Disetujui',
        self::APPROVAL_REJECTED => 'Ditolak',
    ];

    // ===================== RELATIONSHIPS =====================
    
    public function items(): HasMany
    {
        return $this->hasMany(OutboundFocItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // ===================== HELPER METHODS =====================
    
    public static function generateFocNumber(): string
    {
        $date = now()->format('Ymd');
        $last = self::whereDate('created_at', today())->orderBy('id', 'desc')->first();
        $sequence = $last ? intval(substr($last->foc_number, -4)) + 1 : 1;
        return 'FOC' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst($this->reason);
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return self::APPROVAL_STATUSES[$this->approval_status] ?? ucfirst((string) $this->approval_status);
    }

    public function isApprovalPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    // ===================== SCOPES =====================
    
    public function scopeByReason($query, $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopeForCompanyBranch($query, ?int $companyBranchId)
    {
        return $companyBranchId ? $query->where('company_branch_id', $companyBranchId) : $query;
    }
}
