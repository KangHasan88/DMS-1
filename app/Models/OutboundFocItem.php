<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundFocItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'outbound_foc_id',
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
    
    public function outboundFoc(): BelongsTo
    {
        return $this->belongsTo(OutboundFoc::class);
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