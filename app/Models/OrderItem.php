<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'price',
        'discount',
        'quantity',
        'subtotal',
        'is_available',
        'notes',
        'fulfillment_status',
        'purchase_price',
        'supplier_name',
        'market_location',
        'stock_movement_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'price' => 'integer',
        'discount' => 'integer',
        'quantity' => 'integer',
        'subtotal' => 'integer',
        'purchase_price' => 'integer',
    ];

    // ===================== FULFILLMENT STATUS =====================
    
    const FULFILLMENT_PENDING = 'pending';
    const FULFILLMENT_PROCURED = 'procured';
    const FULFILLMENT_FULFILLED = 'fulfilled';
    const FULFILLMENT_UNAVAILABLE = 'unavailable';

    // ===================== RELATIONSHIPS =====================
    
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    // ===================== HELPER METHODS =====================
    
    public function isFulfilled(): bool
    {
        return in_array($this->fulfillment_status, [
            self::FULFILLMENT_FULFILLED,
            self::FULFILLMENT_PROCURED
        ]);
    }

    public function markAsProcured(float $purchasePrice, ?string $supplierName = null, ?string $marketLocation = null): void
    {
        $this->update([
            'purchase_price' => $purchasePrice,
            'supplier_name' => $supplierName,
            'market_location' => $marketLocation,
            'fulfillment_status' => self::FULFILLMENT_PROCURED
        ]);
    }

    public function markAsFulfilledFromStock(int $stockMovementId): void
    {
        $this->update([
            'stock_movement_id' => $stockMovementId,
            'fulfillment_status' => self::FULFILLMENT_FULFILLED
        ]);
    }

    public function markAsUnavailable(): void
    {
        $this->update([
            'is_available' => false,
            'fulfillment_status' => self::FULFILLMENT_UNAVAILABLE
        ]);
        
        $this->order->recalculateTotal();
        
        if ($this->order->useStockMode() && $this->product) {
            StockMovement::create([
                'product_id' => $this->product_id,
                'order_id' => $this->order_id,
                'source_type' => StockMovement::SOURCE_ADJUSTMENT,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => $this->quantity,
                'before_quantity' => $this->product->current_stock,
                'after_quantity' => $this->product->current_stock,
                'reason' => 'Item tidak tersedia untuk Order #' . $this->order->order_number,
                'created_by' => auth()->id(),
            ]);
        }
    }

    public function getFulfillmentStatusLabelAttribute(): string
    {
        $labels = [
            self::FULFILLMENT_PENDING => 'Menunggu',
            self::FULFILLMENT_PROCURED => 'Dibeli',
            self::FULFILLMENT_FULFILLED => 'Diambil dari Stock',
            self::FULFILLMENT_UNAVAILABLE => 'Kosong',
        ];
        
        return $labels[$this->fulfillment_status] ?? ucfirst($this->fulfillment_status);
    }
    
    public function getOriginalPriceAttribute(): int
    {
        return $this->price * $this->quantity;
    }
    
    public function getDiscountAmountAttribute(): int
    {
        return $this->discount ?? 0;
    }
    
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->price > 0 && $this->discount > 0) {
            return ($this->discount / ($this->price * $this->quantity)) * 100;
        }
        return 0;
    }
}