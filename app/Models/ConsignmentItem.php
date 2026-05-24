<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'consignment_id',
        'product_id',
        'quantity',
        'sold_quantity',
        'returned_quantity',
        'price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'sold_quantity' => 'integer',
        'returned_quantity' => 'integer',
        'price' => 'integer',
        'subtotal' => 'integer',
    ];

    // ===================== RELATIONSHIPS =====================
    
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ===================== HELPER METHODS =====================
    
    public function getRemainingQuantityAttribute(): int
    {
        return $this->quantity - $this->sold_quantity - $this->returned_quantity;
    }

    public function isFullySold(): bool
    {
        return $this->sold_quantity >= $this->quantity;
    }

    public function isFullyReturned(): bool
    {
        return $this->returned_quantity >= $this->quantity;
    }

    public function getSoldValueAttribute(): int
    {
        return $this->sold_quantity * $this->price;
    }

    public function getReturnedValueAttribute(): int
    {
        return $this->returned_quantity * $this->price;
    }
}