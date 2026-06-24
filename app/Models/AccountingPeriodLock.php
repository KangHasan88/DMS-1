<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPeriodLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_branch_id',
        'date_from',
        'date_to',
        'status',
        'reason',
        'locked_by',
        'locked_at',
        'unlocked_by',
        'unlocked_at',
        'unlock_reason',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'locked_at' => 'datetime',
        'unlocked_at' => 'datetime',
    ];

    public const STATUS_LOCKED = 'locked';
    public const STATUS_UNLOCKED = 'unlocked';

    public const STATUS_LIST = [
        self::STATUS_LOCKED => 'Terkunci',
        self::STATUS_UNLOCKED => 'Dibuka',
    ];

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function unlockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status === self::STATUS_LOCKED ? 'danger' : 'secondary';
    }

    public function scopeLocked($query)
    {
        return $query->where('status', self::STATUS_LOCKED);
    }
}
