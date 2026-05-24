<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectPurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'direct_purchase_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'integer',
        'subtotal' => 'integer',
    ];

    public function directPurchase(): BelongsTo
    {
        return $this->belongsTo(DirectPurchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isFoc(): bool
    {
        return $this->directPurchase && $this->directPurchase->purchase_type === DirectPurchase::TYPE_FOC;
    }
}