<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'received_quantity',
        'price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'price' => 'integer',
        'subtotal' => 'integer',
    ];

    // ===================== RELATIONSHIPS =====================
    
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ===================== HELPER METHODS =====================
    
    public function getRemainingQuantityAttribute(): int
    {
        return $this->quantity - $this->received_quantity;
    }

    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }

    public function receive(int $quantity, ?string $notes = null): bool
    {
        if ($quantity <= 0 || $quantity > $this->remaining_quantity) {
            return false;
        }

        $this->received_quantity += $quantity;
        $this->save();

        // Add to product stock with source PO
        $product = $this->product;
        $product->addFromPurchaseOrder(
            $quantity,
            $this->purchaseOrder->id,
            'Purchase Order #' . $this->purchaseOrder->po_number . ' - ' . ($notes ?? 'Receiving')
        );

        return true;
    }
}