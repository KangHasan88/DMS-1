<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnablePackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'replacement_value',
        'requires_serial_tracking',
        'is_active',
        'description',
    ];

    protected $casts = [
        'replacement_value' => 'integer',
        'requires_serial_tracking' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const CATEGORY_GALLON = 'gallon';
    public const CATEGORY_BOTTLE = 'bottle';
    public const CATEGORY_CRATE = 'crate';
    public const CATEGORY_GAS_CYLINDER = 'gas_cylinder';
    public const CATEGORY_PALLET = 'pallet';
    public const CATEGORY_CONTAINER = 'container';
    public const CATEGORY_OTHER = 'other';

    public const CATEGORY_LIST = [
        self::CATEGORY_GALLON => 'Galon',
        self::CATEGORY_BOTTLE => 'Botol',
        self::CATEGORY_CRATE => 'Krat',
        self::CATEGORY_GAS_CYLINDER => 'Tabung Gas',
        self::CATEGORY_PALLET => 'Pallet',
        self::CATEGORY_CONTAINER => 'Container',
        self::CATEGORY_OTHER => 'Lainnya',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(ReturnablePackageBalance::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ReturnablePackageMovement::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LIST[$this->category] ?? str($this->category)->headline()->toString();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
