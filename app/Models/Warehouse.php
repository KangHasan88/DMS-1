<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'notes',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const TYPE_MAIN = 'main';
    public const TYPE_TRANSIT = 'transit';
    public const TYPE_RETURN = 'return';
    public const TYPE_DAMAGE = 'damage';

    public const TYPES = [
        self::TYPE_MAIN => 'Gudang Utama',
        self::TYPE_TRANSIT => 'Transit',
        self::TYPE_RETURN => 'Retur',
        self::TYPE_DAMAGE => 'Rusak / Karantina',
    ];

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function defaultId(): ?int
    {
        return static::query()
            ->where('is_default', true)
            ->value('id')
            ?? static::query()->orderBy('sort_order')->orderBy('id')->value('id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
