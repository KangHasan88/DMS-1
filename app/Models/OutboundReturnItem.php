<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'outbound_return_id',
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

    // ===================== RELATIONSHIPS =====================
    
    public function outboundReturn(): BelongsTo
    {
        return $this->belongsTo(OutboundReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ===================== ACCESSORS =====================
    
    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }
}