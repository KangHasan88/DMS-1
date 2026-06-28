<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    protected $fillable = [
        'opname_number',
        'opname_date',
        'warehouse_id',
        'status',
        'notes',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'opname_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_COMPLETED => 'Selesai',
    ];

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'secondary',
        self::STATUS_COMPLETED => 'success',
    ];

    public static function generateNumber(): string
    {
        $date = now()->format('Ymd');
        $lastOpname = self::whereDate('created_at', today())->latest('id')->first();
        $sequence = $lastOpname ? ((int) substr($lastOpname->opname_number, -4)) + 1 : 1;

        return 'SO' . $date . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
