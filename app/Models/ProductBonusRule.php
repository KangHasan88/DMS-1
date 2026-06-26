<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBonusRule extends Model
{
    protected $fillable = [
        'trigger_product_id',
        'bonus_product_id',
        'customer_id',
        'customer_type',
        'company_branch_id',
        'min_quantity',
        'bonus_quantity',
        'starts_at',
        'ends_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'bonus_quantity' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function triggerProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'trigger_product_id');
    }

    public function bonusProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bonus_product_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function getScopeLabelAttribute(): string
    {
        $parts = [];

        if ($this->customer) {
            $parts[] = 'Customer: ' . $this->customer->name;
        } elseif ($this->customer_type) {
            $parts[] = 'Segment: ' . str($this->customer_type)->replace(['-', '_'], ' ')->headline();
        } else {
            $parts[] = 'Semua customer';
        }

        $parts[] = $this->companyBranch ? 'Cabang: ' . $this->companyBranch->name : 'Semua cabang';

        return implode(' / ', $parts);
    }

    public function getBonusLabelAttribute(): string
    {
        return 'Beli min. ' . number_format($this->min_quantity, 0, ',', '.') .
            ' ' . ($this->triggerProduct?->unit?->name ?? 'pcs') .
            ' dapat ' . number_format($this->bonus_quantity, 0, ',', '.') .
            ' ' . ($this->bonusProduct?->unit?->name ?? 'pcs') .
            ' ' . ($this->bonusProduct?->name ?? 'produk bonus');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->ends_at?->isPast() ?? false;
    }
}
