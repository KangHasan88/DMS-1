<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'category', 
        'unit_id',
        'returnable_package_id',
        'returnable_package_quantity_per_unit',
        'returnable_package_default_flow',
        'price', 
        'base_price', 
        'description', 
        'image', 
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'integer',
        'base_price' => 'integer',
        'returnable_package_quantity_per_unit' => 'integer',
    ];

    public const PACKAGING_FLOW_RETURNABLE = 'returnable';
    public const PACKAGING_FLOW_SOLD = 'sold';

    public const PACKAGING_FLOW_LIST = [
        self::PACKAGING_FLOW_RETURNABLE => 'Kemasan Kembali',
        self::PACKAGING_FLOW_SOLD => 'Dijual Putus',
    ];
    
    // ===================== RELATIONSHIPS =====================
    
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function returnablePackage(): BelongsTo
    {
        return $this->belongsTo(ReturnablePackage::class);
    }
    
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    
    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class)->orderBy('created_at', 'desc');
    }
    
    public function latestPriceHistory(): HasOne
    {
        return $this->hasOne(ProductPriceHistory::class)->latest();
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(ProductPriceRule::class);
    }
    
    public function stock(): HasOne
    {
        return $this->hasOne(ProductStock::class);
    }
    
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
    
    // ===================== STOCK HELPER METHODS =====================
    
    public function getCurrentStockAttribute(): int
    {
        return $this->stock ? $this->stock->quantity : 0;
    }
    
    public function getCurrentConsignmentStockAttribute(): int
    {
        return $this->stock ? $this->stock->consignment_quantity : 0;
    }
    
    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock && $this->stock->quantity >= $quantity;
    }
    
    // ===================== INBOUND INTEGRATION =====================
    
    public function addFromPurchaseOrder(int $quantity, int $purchaseOrderId, ?string $reason = null): void
    {
        if (!$this->stock) {
            $this->stock()->create(['quantity' => 0]);
            $this->refresh();
        }
        
        $this->stock->addFromPurchaseOrder($quantity, $purchaseOrderId, $reason);
    }
    
    public function addFromDirectPurchase(int $quantity, int $directPurchaseId, ?string $reason = null): void
    {
        if (!$this->stock) {
            $this->stock()->create(['quantity' => 0]);
            $this->refresh();
        }
        
        $this->stock->addFromDirectPurchase($quantity, $directPurchaseId, $reason);
    }
    
    public function addFromFoc(int $quantity, ?string $reason = null): void
    {
        if (!$this->stock) {
            $this->stock()->create(['quantity' => 0]);
            $this->refresh();
        }
        
        $this->stock->addFromFoc($quantity, $reason);
    }
    
    public function addConsignmentStock(int $quantity, int $consignmentId, ?string $reason = null): void
    {
        if (!$this->stock) {
            $this->stock()->create(['quantity' => 0, 'consignment_quantity' => 0]);
            $this->refresh();
        }
        
        $this->stock->addConsignmentStock($quantity, $consignmentId, $reason);
    }
    
    // ===================== OUTBOUND INTEGRATION =====================
    
    public function reduceForOrder(int $quantity, int $orderId, ?string $reason = null): bool
    {
        if (!$this->stock) {
            return false;
        }
        
        return $this->stock->reduceForOrder($quantity, $orderId, $reason);
    }

    public function restoreForOrder(int $quantity, int $orderId, ?string $reason = null): void
    {
        if (!$this->stock) {
            $this->stock()->create(['quantity' => 0]);
            $this->refresh();
        }

        $this->stock->restoreForOrder($quantity, $orderId, $reason);
    }
    
    public function reduceForFocOut(int $quantity, int $focId, ?string $reason = null): bool
    {
        if (!$this->stock) {
            return false;
        }
        
        return $this->stock->reduceForFocOut($quantity, $focId, $reason);
    }
    
    public function reduceForReturnOut(int $quantity, int $returnId, ?string $reason = null): bool
    {
        if (!$this->stock) {
            return false;
        }
        
        return $this->stock->reduceForReturnOut($quantity, $returnId, $reason);
    }
    
    public function adjustStock(int $newQuantity, ?string $reason = null): void
    {
        if (!$this->stock) {
            $this->stock()->create(['quantity' => 0]);
            $this->refresh();
        }
        
        $this->stock->adjustStock($newQuantity, $reason);
    }
    
    public function reduceConsignmentStock(int $quantity, int $consignmentId, ?string $reason = null): bool
    {
        if (!$this->stock) {
            return false;
        }
        
        return $this->stock->reduceConsignmentStock($quantity, $consignmentId, $reason);
    }
    
    public function returnConsignmentStock(int $quantity, int $consignmentId, ?string $reason = null): bool
    {
        if (!$this->stock) {
            return false;
        }
        
        return $this->stock->returnConsignmentStock($quantity, $consignmentId, $reason);
    }
    
    // ===================== ACCESSORS =====================
    
    public function getUnitNameAttribute(): ?string
    {
        return $this->unit?->name;
    }
    
    public function getUnitSymbolAttribute(): ?string
    {
        return $this->unit?->symbol;
    }
    
    public function getFormattedUnitAttribute(): string
    {
        if ($this->unit) {
            return $this->unit->symbol ?: $this->unit->name;
        }
        return '-';
    }

    public function hasReturnablePackaging(): bool
    {
        return $this->returnable_package_id !== null
            && $this->returnable_package_quantity_per_unit > 0
            && $this->returnable_package_default_flow !== null;
    }

    public function returnablePackageQuantityFor(int $productQuantity): int
    {
        return max(0, $productQuantity) * max(0, (int) $this->returnable_package_quantity_per_unit);
    }

    public function getReturnablePackageDefaultFlowLabelAttribute(): string
    {
        return self::PACKAGING_FLOW_LIST[$this->returnable_package_default_flow] ?? '-';
    }
    
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
    
    public function getFormattedBasePriceAttribute(): string
    {
        return $this->base_price ? 'Rp ' . number_format($this->base_price, 0, ',', '.') : '-';
    }
    
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        
        $defaultImages = [
            'Sayur' => '/images/default-vegetable.png',
            'Buah' => '/images/default-fruit.png',
            'Lauk' => '/images/default-meat.png',
            'Bumbu' => '/images/default-spice.png',
        ];
        
        return asset($defaultImages[$this->category] ?? '/images/default-product.png');
    }
    
    public function getMarginAttribute(): ?int
    {
        if ($this->base_price && $this->price) {
            return $this->price - $this->base_price;
        }
        return null;
    }
    
    public function getMarginPercentageAttribute(): ?float
    {
        if ($this->base_price && $this->price && $this->price > 0) {
            return round(($this->price - $this->base_price) / $this->price * 100, 2);
        }
        return null;
    }
    
    // ===================== HELPER METHODS =====================
    
    public function recordPriceChange(array $oldData, array $newData, int $userId, ?string $reason = null): ?ProductPriceHistory
    {
        $priceChanged = ($oldData['price'] ?? null) != ($newData['price'] ?? null);
        $basePriceChanged = ($oldData['base_price'] ?? null) != ($newData['base_price'] ?? null);
        
        if ($priceChanged || $basePriceChanged) {
            return $this->priceHistories()->create([
                'user_id' => $userId,
                'old_price' => $oldData['price'] ?? null,
                'new_price' => $newData['price'] ?? null,
                'old_base_price' => $oldData['base_price'] ?? null,
                'new_base_price' => $newData['base_price'] ?? null,
                'reason' => $reason,
            ]);
        }
        
        return null;
    }
    
    public function getTotalSoldAttribute(): int
    {
        return $this->orderItems()
            ->where('is_available', true)
            ->sum('quantity');
    }
    
    public function getTotalRevenueAttribute(): int
    {
        return $this->orderItems()
            ->where('is_available', true)
            ->sum('subtotal');
    }
    
    public function isActive(): bool
    {
        return $this->is_active;
    }
    
    public function activate(): self
    {
        $this->update(['is_active' => true]);
        return $this;
    }
    
    public function deactivate(): self
    {
        $this->update(['is_active' => false]);
        return $this;
    }
    
    public function toggleActive(): self
    {
        $this->update(['is_active' => !$this->is_active]);
        return $this;
    }
    
    // ===================== SCOPES =====================
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
    
    public function scopeByUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }
    
    public function scopeWithPriceChanges($query)
    {
        return $query->whereHas('priceHistories');
    }
    
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('category', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
    }
    
    public function scopeMinMargin($query, $minMargin)
    {
        return $query->whereRaw('(price - base_price) >= ?', [$minMargin]);
    }
    
    public function scopeBestSeller($query, $limit = 10)
    {
        return $query->withCount(['orderItems as total_sold' => function($q) {
                $q->where('is_available', true);
            }])
            ->orderBy('total_sold', 'desc')
            ->limit($limit);
    }
    
    // ===================== STOCK SCOPES =====================
    
    public function scopeInStock($query)
    {
        return $query->whereHas('stock', function($q) {
            $q->where('quantity', '>', 0);
        });
    }
    
    public function scopeLowStock($query)
    {
        return $query->whereHas('stock', function($q) {
            $q->whereRaw('quantity <= min_stock')
              ->where('quantity', '>', 0);
        });
    }
    
    public function scopeOutOfStock($query)
    {
        return $query->whereDoesntHave('stock', function($q) {
            $q->where('quantity', '>', 0);
        })->orWhereHas('stock', function($q) {
            $q->where('quantity', 0);
        });
    }
    
    public function scopeHasConsignment($query)
    {
        return $query->whereHas('stock', function($q) {
            $q->where('consignment_quantity', '>', 0);
        });
    }
}
