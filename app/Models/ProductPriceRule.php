<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceRule extends Model
{
    protected $fillable = [
        'product_id',
        'customer_id',
        'customer_type',
        'company_branch_id',
        'price',
        'starts_at',
        'ends_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'price' => 'integer',
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
}
