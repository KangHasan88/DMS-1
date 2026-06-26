<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentRequest extends Model
{
    protected $fillable = [
        'request_number',
        'product_id',
        'company_branch_id',
        'current_quantity',
        'new_quantity',
        'quantity_difference',
        'reason',
        'approval_request_id',
        'approval_status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_note',
    ];

    protected $casts = [
        'current_quantity' => 'integer',
        'new_quantity' => 'integer',
        'quantity_difference' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    public const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING => 'Menunggu Approval',
        self::APPROVAL_APPROVED => 'Disetujui',
        self::APPROVAL_REJECTED => 'Ditolak',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockAdjustmentRequest $request) {
            $request->request_number ??= self::generateRequestNumber();
            $request->approval_status ??= self::APPROVAL_PENDING;
        });
    }

    public static function generateRequestNumber(): string
    {
        $prefix = 'SAR' . now()->format('Ymd');
        $last = self::where('request_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();
        $next = $last ? ((int) substr($last->request_number, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return self::APPROVAL_STATUSES[$this->approval_status] ?? ucfirst((string) $this->approval_status);
    }

    public function isApprovalPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }
}
