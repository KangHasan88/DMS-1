<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    protected $fillable = [
        'request_number',
        'approval_type',
        'approvable_type',
        'approvable_id',
        'company_branch_id',
        'title',
        'description',
        'status',
        'request_note',
        'decision_note',
        'payload',
        'requested_by',
        'decided_by',
        'requested_at',
        'decided_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public const TYPE_GENERAL = 'general';
    public const TYPE_PURCHASE_ORDER = 'purchase_order';
    public const TYPE_OUTBOUND_FOC = 'outbound_foc';
    public const TYPE_STOCK_ADJUSTMENT = 'stock_adjustment';
    public const TYPE_VOID_REVERSAL = 'void_reversal';
    public const TYPE_DISCOUNT_OVERRIDE = 'discount_override';
    public const TYPE_PRICE_CHANGE = 'price_change';

    public const TYPES = [
        self::TYPE_GENERAL => 'Umum',
        self::TYPE_PURCHASE_ORDER => 'Purchase Order',
        self::TYPE_OUTBOUND_FOC => 'Bonus / FOC',
        self::TYPE_STOCK_ADJUSTMENT => 'Penyesuaian Stok',
        self::TYPE_VOID_REVERSAL => 'Void / Reversal',
        self::TYPE_DISCOUNT_OVERRIDE => 'Override Diskon',
        self::TYPE_PRICE_CHANGE => 'Perubahan Harga',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApprovalRequest $approvalRequest) {
            $approvalRequest->request_number ??= self::generateRequestNumber();
            $approvalRequest->status ??= self::STATUS_PENDING;
            $approvalRequest->requested_at ??= now();
            $approvalRequest->requested_by ??= auth()->id();
        });
    }

    public static function generateRequestNumber(): string
    {
        $prefix = 'APR-' . now()->format('Ymd');
        $next = self::where('request_number', 'like', $prefix . '%')->count() + 1;

        return $prefix . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->approval_type] ?? ucfirst(str_replace('_', ' ', $this->approval_type));
    }
}
