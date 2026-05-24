<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'delivery_date',
        'delivery_time_slot',
        'address',
        'latitude',
        'longitude',
        'delivery_fee',
        'packing_fee',
        'subtotal',
        'total',
        'status',
        'notes',
        'admin_notes',
        'shopping_notes',
        'paid_at',
        'checking_stock_at',
        'procuring_at',
        'repacked_at',
        'ready_at',
        'shipped_at',
        'delivered_at',
        'tracking_code',
        'order_source',
        'payment_method',
        'payment_reference',
        'fulfillment_type',
        'discount_type',
        'discount_value',
        'discount_amount',
        'shipping_type',
        'shipping_weight',
        'shipping_distance',
        'shipping_rate',
        'include_ppn',
        'ppn_rate',
        'ppn_amount',
        'grand_total',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'paid_at' => 'datetime',
        'checking_stock_at' => 'datetime',
        'procuring_at' => 'datetime',
        'repacked_at' => 'datetime',
        'ready_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'subtotal' => 'integer',
        'total' => 'integer',
        'delivery_fee' => 'integer',
        'packing_fee' => 'integer',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'integer',
        'shipping_weight' => 'integer',
        'shipping_distance' => 'integer',
        'shipping_rate' => 'integer',
        'include_ppn' => 'boolean',
        'ppn_rate' => 'decimal:2',
        'ppn_amount' => 'integer',
        'grand_total' => 'integer',
    ];

    // ===================== STATUS CONSTANTS =====================
    
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PAID = 'paid';
    const STATUS_CHECKING_STOCK = 'checking_stock';
    const STATUS_PROCURING = 'procuring';
    const STATUS_REPACKING = 'repacking';
    const STATUS_READY = 'ready';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LIST = [
        self::STATUS_PENDING_PAYMENT => 'Menunggu Bayar',
        self::STATUS_PAID => 'Sudah Bayar',
        self::STATUS_CHECKING_STOCK => 'Cek Stock',
        self::STATUS_PROCURING => 'Belanja',
        self::STATUS_REPACKING => 'Repacking',
        self::STATUS_READY => 'Siap Kirim',
        self::STATUS_SHIPPED => 'Dikirim',
        self::STATUS_DELIVERED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    const STATUS_COLORS = [
        self::STATUS_PENDING_PAYMENT => 'warning',
        self::STATUS_PAID => 'info',
        self::STATUS_CHECKING_STOCK => 'info',
        self::STATUS_PROCURING => 'info',
        self::STATUS_REPACKING => 'info',
        self::STATUS_READY => 'success',
        self::STATUS_SHIPPED => 'success',
        self::STATUS_DELIVERED => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    // ===================== ORDER SOURCE CONSTANTS =====================
    
    const ORDER_SOURCE_APP = 'app';
    const ORDER_SOURCE_ADMIN = 'admin';

    // ===================== FULFILLMENT TYPE CONSTANTS =====================
    
    const FULFILLMENT_STOCK = 'stock';
    const FULFILLMENT_JIT = 'jit';

    // ===================== PAYMENT METHOD CONSTANTS =====================
    
    const PAYMENT_GATEWAY = 'gateway';
    const PAYMENT_MANUAL = 'manual';
    const PAYMENT_WALLET = 'wallet';

    // ===================== DISCOUNT TYPE CONSTANTS =====================
    
    const DISCOUNT_NONE = 'none';
    const DISCOUNT_PERCENT = 'percent';
    const DISCOUNT_NOMINAL = 'nominal';

    // ===================== SHIPPING TYPE CONSTANTS =====================
    
    const SHIPPING_FLAT = 'flat';
    const SHIPPING_WEIGHT = 'weight';
    const SHIPPING_DISTANCE = 'distance';

    // ===================== RELATIONSHIPS =====================
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    // ===================== HELPER METHODS =====================
    
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $lastOrder = self::whereDate('created_at', today())->orderBy('id', 'desc')->first();
        $sequence = $lastOrder ? intval(substr($lastOrder->order_number, -4)) + 1 : 1;
        
        return 'KMG' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function canUpdateStatus(): bool
    {
        return !in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    public function isFromApp(): bool
    {
        return $this->order_source === self::ORDER_SOURCE_APP;
    }

    public function isFromAdmin(): bool
    {
        return $this->order_source === self::ORDER_SOURCE_ADMIN;
    }

    public function useStockMode(): bool
    {
        return $this->fulfillment_type === self::FULFILLMENT_STOCK;
    }

    public function useJitMode(): bool
    {
        return $this->fulfillment_type === self::FULFILLMENT_JIT;
    }

    public function updateStatus(string $newStatus, ?string $notes = null): bool
    {
        if (!$this->canUpdateStatus() && $newStatus !== self::STATUS_CANCELLED) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        // Update timestamp based on status
        switch ($newStatus) {
            case self::STATUS_PAID:
                $this->paid_at = now();
                break;
            case self::STATUS_CHECKING_STOCK:
                $this->checking_stock_at = now();
                break;
            case self::STATUS_PROCURING:
                $this->procuring_at = now();
                break;
            case self::STATUS_REPACKING:
                $this->repacked_at = now();
                break;
            case self::STATUS_READY:
                $this->ready_at = now();
                break;
            case self::STATUS_SHIPPED:
                $this->shipped_at = now();
                break;
            case self::STATUS_DELIVERED:
                $this->delivered_at = now();
                break;
        }

        if ($notes) {
            $this->admin_notes = ($this->admin_notes ? $this->admin_notes . "\n" : '') . 
                                 now()->format('d/m/Y H:i') . " - " . $notes;
        }

        return $this->save();
    }

    public function canTransitionTo(string $newStatus): bool
    {
        if ($newStatus === self::STATUS_CANCELLED) {
            return !in_array($this->status, [self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_CANCELLED], true);
        }

        $allowed = [
            self::STATUS_PENDING_PAYMENT => [self::STATUS_PAID],
            self::STATUS_PAID => [
                $this->useStockMode() ? self::STATUS_CHECKING_STOCK : self::STATUS_PROCURING,
            ],
            self::STATUS_CHECKING_STOCK => [self::STATUS_REPACKING],
            self::STATUS_PROCURING => [self::STATUS_REPACKING],
            self::STATUS_REPACKING => [self::STATUS_READY],
            self::STATUS_READY => [self::STATUS_SHIPPED],
            self::STATUS_SHIPPED => [self::STATUS_DELIVERED],
        ];

        return in_array($newStatus, $allowed[$this->status] ?? [], true);
    }

    public function recalculateTotal(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        
        // Calculate order discount
        $discountAmount = 0;
        if ($this->discount_type === self::DISCOUNT_PERCENT) {
            $discountAmount = $this->subtotal * $this->discount_value / 100;
        } elseif ($this->discount_type === self::DISCOUNT_NOMINAL) {
            $discountAmount = $this->discount_value;
        }
        
        $afterDiscount = $this->subtotal - $discountAmount;
        
        // Calculate shipping cost
        $shippingCost = $this->shipping_rate;
        if ($this->shipping_type === self::SHIPPING_WEIGHT && $this->shipping_weight) {
            $shippingCost = $this->shipping_weight * $this->shipping_rate;
        } elseif ($this->shipping_type === self::SHIPPING_DISTANCE && $this->shipping_distance) {
            $shippingCost = $this->shipping_distance * $this->shipping_rate;
        }
        
        // Calculate PPN
        $ppnAmount = 0;
        if ($this->include_ppn) {
            $ppnAmount = ($afterDiscount + $shippingCost + $this->packing_fee) * ($this->ppn_rate / 100);
        }
        
        $this->discount_amount = $discountAmount;
        $this->ppn_amount = $ppnAmount;
        $this->grand_total = $afterDiscount + $shippingCost + $this->packing_fee + $ppnAmount;
        $this->total = $this->grand_total;
        
        $this->save();
    }

    public function processStockReduction(): bool
    {
        if (!$this->useStockMode()) {
            return true;
        }
        
        $allAvailable = true;
        
        foreach ($this->items as $item) {
            if ($item->fulfillment_status === OrderItem::FULFILLMENT_FULFILLED) {
                continue;
            }

            $product = $item->product;
            if (!$product->hasStock($item->quantity)) {
                $allAvailable = false;
                $item->markAsUnavailable();
                continue;
            }
            
            $product->reduceForOrder(
                $item->quantity,
                $this->id,
                'Order #' . $this->order_number
            );
            
            $item->update([
                'fulfillment_status' => OrderItem::FULFILLMENT_FULFILLED
            ]);
        }
        
        $this->recalculateTotal();
        
        return $allAvailable;
    }

    public function restoreAllocatedStock(?string $reason = null): void
    {
        if (!$this->useStockMode()) {
            return;
        }

        $this->loadMissing('items.product.stock');

        foreach ($this->items as $item) {
            if ($item->fulfillment_status !== OrderItem::FULFILLMENT_FULFILLED || !$item->product) {
                continue;
            }

            $item->product->restoreForOrder(
                $item->quantity,
                $this->id,
                $reason ?? 'Order #' . $this->order_number . ' dibatalkan'
            );

            $item->update([
                'fulfillment_status' => OrderItem::FULFILLMENT_PENDING,
                'stock_movement_id' => null,
            ]);
        }
    }

    public function isFullyFulfilled(): bool
    {
        if (!$this->useStockMode()) {
            return true;
        }
        
        return $this->items()
            ->where('fulfillment_status', '!=', OrderItem::FULFILLMENT_FULFILLED)
            ->count() == 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    // ===================== SCOPES =====================
    
    public function scopePendingPayment($query)
    {
        return $query->where('status', self::STATUS_PENDING_PAYMENT);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeCheckingStock($query)
    {
        return $query->where('status', self::STATUS_CHECKING_STOCK);
    }

    public function scopeProcuring($query)
    {
        return $query->where('status', self::STATUS_PROCURING);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    public function scopeFromApp($query)
    {
        return $query->where('order_source', self::ORDER_SOURCE_APP);
    }

    public function scopeFromAdmin($query)
    {
        return $query->where('order_source', self::ORDER_SOURCE_ADMIN);
    }

    public function scopeStockMode($query)
    {
        return $query->where('fulfillment_type', self::FULFILLMENT_STOCK);
    }

    public function scopeJitMode($query)
    {
        return $query->where('fulfillment_type', self::FULFILLMENT_JIT);
    }
}
