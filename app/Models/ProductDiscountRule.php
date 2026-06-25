<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDiscountRule extends Model
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_NOMINAL = 'nominal';

    protected $fillable = [
        'product_id',
        'customer_id',
        'customer_type',
        'company_branch_id',
        'discount_type',
        'discount_value',
        'min_quantity',
        'starts_at',
        'ends_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_quantity' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

        $parts[] = $this->product ? 'Produk: ' . $this->product->name : 'Semua produk';

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

    public function getDiscountLabelAttribute(): string
    {
        if ($this->discount_type === self::TYPE_PERCENT) {
            return rtrim(rtrim(number_format((float) $this->discount_value, 2, ',', '.'), '0'), ',') . '%';
        }

        return 'Rp ' . number_format((float) $this->discount_value, 0, ',', '.') . ' / unit';
    }
}
