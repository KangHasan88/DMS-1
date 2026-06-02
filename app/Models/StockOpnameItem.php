<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'system_quantity',
        'counted_quantity',
        'difference_quantity',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'integer',
        'counted_quantity' => 'integer',
        'difference_quantity' => 'integer',
    ];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function recountDifference(): void
    {
        $this->difference_quantity = is_null($this->counted_quantity)
            ? 0
            : $this->counted_quantity - $this->system_quantity;
    }
}
