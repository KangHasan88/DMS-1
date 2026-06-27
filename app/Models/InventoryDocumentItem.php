<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryDocumentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_document_id',
        'product_id',
        'quantity',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(InventoryDocument::class, 'inventory_document_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
