<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'consignment_quantity',
        'min_stock',
        'max_stock',
        'last_updated_at',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'consignment_quantity' => 'integer',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
        'last_updated_at' => 'datetime',
    ];

    // ===================== RELATIONSHIPS =====================
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ===================== HELPER METHODS =====================
    
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_stock;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    public function hasStock(int $quantity = 1): bool
    {
        return $this->quantity >= $quantity;
    }

    public function getConsignmentStockAttribute(): int
    {
        return $this->consignment_quantity;
    }

    // ===================== INBOUND METHODS =====================
    
    /**
     * Add stock from Purchase Order
     */
    public function addFromPurchaseOrder(int $quantity, int $purchaseOrderId, ?string $reason = null): void
    {
        $before = $this->quantity;
        $this->quantity += $quantity;
        $this->last_updated_at = now();
        $this->updated_by = auth()->id();
        $this->save();
        
        StockMovement::create([
            'product_id' => $this->product_id,
            'source_type' => StockMovement::SOURCE_PURCHASE_ORDER,
            'source_id' => $purchaseOrderId,
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->quantity,
            'reason' => $reason ?? 'Purchase Order #' . $purchaseOrderId,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Add stock from Direct Purchase
     */
    public function addFromDirectPurchase(int $quantity, int $directPurchaseId, ?string $reason = null): void
    {
        $before = $this->quantity;
        $this->quantity += $quantity;
        $this->last_updated_at = now();
        $this->updated_by = auth()->id();
        $this->save();
        
        StockMovement::create([
            'product_id' => $this->product_id,
            'source_type' => StockMovement::SOURCE_DIRECT_PURCHASE,
            'source_id' => $directPurchaseId,
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->quantity,
            'reason' => $reason ?? 'Direct Purchase #' . $directPurchaseId,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Add stock from FOC (Bonus from supplier)
     */
    public function addFromFoc(int $quantity, ?string $reason = null): void
    {
        $before = $this->quantity;
        $this->quantity += $quantity;
        $this->last_updated_at = now();
        $this->updated_by = auth()->id();
        $this->save();
        
        StockMovement::create([
            'product_id' => $this->product_id,
            'source_type' => StockMovement::SOURCE_FOC,
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->quantity,
            'reason' => $reason ?? 'FOC (Bonus from supplier)',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Add consignment stock
     */
    public function addConsignmentStock(int $quantity, int $consignmentId, ?string $reason = null): void
    {
        $before = $this->consignment_quantity;
        $this->consignment_quantity += $quantity;
        $this->save();
        
        StockMovement::create([
            'product_id' => $this->product_id,
            'source_type' => StockMovement::SOURCE_CONSIGNMENT,
            'source_id' => $consignmentId,
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->consignment_quantity,
            'reason' => $reason ?? 'Consignment #' . $consignmentId,
            'created_by' => auth()->id(),
        ]);
    }

    // ===================== OUTBOUND METHODS =====================
    
    /**
     * Reduce stock from Sales Order
     */
    public function reduceForOrder(int $quantity, int $orderId, ?string $reason = null): bool
    {
        if (!$this->hasStock($quantity)) {
            return false;
        }
        
        $before = $this->quantity;
        $this->quantity -= $quantity;
        $this->last_updated_at = now();
        $this->updated_by = auth()->id();
        $this->save();
        
        StockMovement::create([
            'product_id' => $this->product_id,
            'order_id' => $orderId,
            'source_type' => StockMovement::SOURCE_ORDER,
            'source_id' => $orderId,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->quantity,
            'reason' => $reason ?? 'Sales Order #' . $orderId,
            'created_by' => auth()->id(),
        ]);
        
        return true;
    }

    /**
     * Reduce stock from FOC Out (Hadiah)
     */
    public function reduceForFocOut(int $quantity, int $focId, ?string $reason = null): bool
    {
        if (!$this->hasStock($quantity)) {
            return false;
        }
        
        $before = $this->quantity;
        $this->quantity -= $quantity;
        $this->last_updated_at = now();
        $this->updated_by = auth()->id();
        $this->save();
        
        StockMovement::create([
            'product_id' => $this->product_id,
            'source_type' => StockMovement::SOURCE_FOC_OUT,
            'source_id' => $focId,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->quantity,
            'reason' => $reason ?? 'FOC Out #' . $focId,
            'created_by' => auth()->id(),
        ]);
        
        return true;
    }

    /**
     * Reduce stock from Return Out (Retur)
     */
    public function reduceForReturnOut(int $quantity, int $returnId, ?string $reason = null): bool
    {
        if (!$this->hasStock($quantity)) {
            return false;
        }
        
        $before = $this->quantity;
        $this->quantity -= $quantity;
        $this->last_updated_at = now();
        $this->updated_by = auth()->id();
        $this->save();
        
        StockMovement::create([
            'product_id' => $this->product_id,
            'source_type' => StockMovement::SOURCE_RETURN_OUT,
            'source_id' => $returnId,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->quantity,
            'reason' => $reason ?? 'Return Out #' . $returnId,
            'created_by' => auth()->id(),
        ]);
        
        return true;
    }

    /**
     * Adjust stock (manual adjustment)
     */
    public function adjustStock(int $newQuantity, ?string $reason = null): void
    {
        $before = $this->quantity;
        $difference = $newQuantity - $before;
        
        $this->quantity = $newQuantity;
        $this->last_updated_at = now();
        $this->updated_by = auth()->id();
        $this->save();
        
        StockMovement::create([
            'product_id' => $this->product_id,
            'source_type' => StockMovement::SOURCE_ADJUSTMENT,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => abs($difference),
            'before_quantity' => $before,
            'after_quantity' => $newQuantity,
            'reason' => $reason ?? 'Manual stock adjustment',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Reduce consignment stock (when consignment sold)
     */
    public function reduceConsignmentStock(int $quantity, int $consignmentId, ?string $reason = null): bool
    {
        if ($this->consignment_quantity < $quantity) {
            return false;
        }
        
        $before = $this->consignment_quantity;
        $this->consignment_quantity -= $quantity;
        $this->save();
        
        StockMovement::create([
            'product_id' => $this->product_id,
            'source_type' => StockMovement::SOURCE_CONSIGNMENT_SALE,
            'source_id' => $consignmentId,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->consignment_quantity,
            'reason' => $reason ?? 'Consignment sold #' . $consignmentId,
            'created_by' => auth()->id(),
        ]);
        
        return true;
    }

    /**
     * Return consignment stock (back to supplier)
     */
    public function returnConsignmentStock(int $quantity, int $consignmentId, ?string $reason = null): bool
    {
        if ($this->consignment_quantity < $quantity) {
            return false;
        }
        
        $before = $this->consignment_quantity;
        $this->consignment_quantity -= $quantity;
        $this->save();
        
        StockMovement::create([
            'product_id' => $this->product_id,
            'source_type' => StockMovement::SOURCE_CONSIGNMENT_RETURN,
            'source_id' => $consignmentId,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->consignment_quantity,
            'reason' => $reason ?? 'Consignment return #' . $consignmentId,
            'created_by' => auth()->id(),
        ]);
        
        return true;
    }
}