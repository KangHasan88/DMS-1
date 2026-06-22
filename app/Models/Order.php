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
        'created_by',
        'salesperson_id',
        'route_session_id',
        'company_branch_id',
        'invoice_address_id',
        'shipping_address_id',
        'order_number',
        'request_token',
        'delivery_date',
        'delivery_time_slot',
        'address',
        'invoice_address_snapshot',
        'shipping_address_snapshot',
        'shipping_recipient_name',
        'shipping_recipient_phone',
        'shipping_same_as_invoice',
        'latitude',
        'longitude',
        'delivery_fee',
        'packing_fee',
        'requires_packing',
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
        'payment_timing',
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
        'requires_packing' => 'boolean',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'integer',
        'shipping_weight' => 'integer',
        'shipping_distance' => 'integer',
        'shipping_rate' => 'integer',
        'include_ppn' => 'boolean',
        'shipping_same_as_invoice' => 'boolean',
        'ppn_rate' => 'decimal:2',
        'ppn_amount' => 'integer',
        'grand_total' => 'integer',
    ];

    // ===================== STATUS CONSTANTS =====================
    
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PAID = 'paid';
    const STATUS_CHECKING_STOCK = 'checking_stock';
    const STATUS_PICKING = 'picking';
    const STATUS_PROCURING = 'procuring';
    const STATUS_REPACKING = 'repacking';
    const STATUS_READY = 'ready';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LIST = [
        self::STATUS_PENDING_PAYMENT => 'Menunggu Bayar',
        self::STATUS_CHECKING_STOCK => 'Alokasi Stok',
        self::STATUS_PICKING => 'Picking',
        self::STATUS_PROCURING => 'Belanja BLJ',
        self::STATUS_REPACKING => 'Packing / Repack',
        self::STATUS_READY => 'Siap Kirim',
        self::STATUS_SHIPPED => 'Dalam Pengiriman',
        self::STATUS_PAID => 'Sudah Bayar',
        self::STATUS_DELIVERED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    const STATUS_COLORS = [
        self::STATUS_PENDING_PAYMENT => 'warning',
        self::STATUS_PAID => 'info',
        self::STATUS_CHECKING_STOCK => 'info',
        self::STATUS_PICKING => 'info',
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
    const ORDER_SOURCE_SFA = 'sfa';
    const ORDER_SOURCE_TELESALES = 'telesales';

    const ORDER_SOURCE_LIST = [
        self::ORDER_SOURCE_ADMIN => 'Admin',
        self::ORDER_SOURCE_SFA => 'SFA',
        self::ORDER_SOURCE_TELESALES => 'Telesales',
        self::ORDER_SOURCE_APP => 'Aplikasi Customer',
    ];

    // ===================== FULFILLMENT TYPE CONSTANTS =====================
    
    const FULFILLMENT_STOCK = 'stock';
    const FULFILLMENT_JIT = 'jit';

    // ===================== PAYMENT METHOD CONSTANTS =====================
    
    const PAYMENT_GATEWAY = 'gateway';
    const PAYMENT_MANUAL = 'manual';
    const PAYMENT_WALLET = 'wallet';

    // ===================== PAYMENT TIMING CONSTANTS =====================

    const PAYMENT_TIMING_PRE_PAID = 'pre_paid';
    const PAYMENT_TIMING_POST_PAID = 'post_paid';

    const PAYMENT_TIMING_LIST = [
        self::PAYMENT_TIMING_PRE_PAID => 'Pre-paid',
        self::PAYMENT_TIMING_POST_PAID => 'Post-paid',
    ];

    // ===================== DISCOUNT TYPE CONSTANTS =====================
    
    const DISCOUNT_NONE = 'none';
    const DISCOUNT_PERCENT = 'percent';
    const DISCOUNT_NOMINAL = 'nominal';

    // ===================== SHIPPING TYPE CONSTANTS =====================
    
    const SHIPPING_NONE = 'none';
    const SHIPPING_FLAT = 'flat';
    const SHIPPING_WEIGHT = 'weight';
    const SHIPPING_DISTANCE = 'distance';

    // ===================== RELATIONSHIPS =====================
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function routeSession(): BelongsTo
    {
        return $this->belongsTo(DeliveryRouteSession::class, 'route_session_id');
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function invoiceAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'invoice_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id');
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

    public function documentNumber(string $prefix, ?string $companyCode = null, ?string $branchCode = null): string
    {
        $date = $this->created_at ? $this->created_at->format('Ymd') : now()->format('Ymd');
        $digits = preg_replace('/\D/', '', (string) $this->order_number);
        $sequence = $digits ? substr($digits, -4) : str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);

        return strtoupper($prefix) . '-'
            . $this->documentCodePart($companyCode ?: 'KMG', 'KMG')
            . $this->documentCodePart($branchCode ?: 'MAI', 'MAI')
            . $date
            . $sequence;
    }

    private function documentCodePart(string $value, string $fallback): string
    {
        $code = preg_replace('/[^A-Za-z0-9]/', '', $value) ?: $fallback;

        return substr(strtoupper($code), 0, 3);
    }

    public function canUpdateStatus(): bool
    {
        return !in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    public function canEditOrder(): bool
    {
        if ($this->isPrePaid()) {
            return $this->status === self::STATUS_PENDING_PAYMENT;
        }

        return in_array($this->status, [
            self::STATUS_CHECKING_STOCK,
            self::STATUS_PROCURING,
        ], true);
    }

    public function canDeleteOrder(): bool
    {
        return $this->canEditOrder();
    }

    public function canViewDeliveryOrderDocument(): bool
    {
        return in_array($this->status, [
            self::STATUS_READY,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
        ], true) || ($this->isPostPaid() && $this->status === self::STATUS_PAID);
    }

    public function isFromApp(): bool
    {
        return $this->order_source === self::ORDER_SOURCE_APP;
    }

    public function isFromAdmin(): bool
    {
        return $this->order_source === self::ORDER_SOURCE_ADMIN;
    }

    public function getOrderSourceLabelAttribute(): string
    {
        return self::ORDER_SOURCE_LIST[$this->order_source] ?? ucfirst((string) $this->order_source);
    }

    public function useStockMode(): bool
    {
        return $this->fulfillment_type === self::FULFILLMENT_STOCK;
    }

    public function useJitMode(): bool
    {
        return $this->fulfillment_type === self::FULFILLMENT_JIT;
    }

    public function isPrePaid(): bool
    {
        return $this->payment_timing === self::PAYMENT_TIMING_PRE_PAID;
    }

    public function isPostPaid(): bool
    {
        return $this->payment_timing === self::PAYMENT_TIMING_POST_PAID;
    }

    public function requiresPacking(): bool
    {
        return (bool) $this->requires_packing;
    }

    public function updateStatus(string $newStatus, ?string $notes = null): bool
    {
        if (!array_key_exists($newStatus, self::STATUS_LIST)) {
            return false;
        }

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

        $saved = $this->save();

        if ($saved && $oldStatus !== $newStatus) {
            ActivityLog::record('orders', 'status_changed', "Order {$this->order_number} berubah status", $this, [
                'order_number' => $this->order_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes,
            ]);
        }

        return $saved;
    }

    public function canTransitionTo(string $newStatus): bool
    {
        if (!array_key_exists($newStatus, self::STATUS_LIST)) {
            return false;
        }

        if ($newStatus === self::STATUS_CANCELLED) {
            return !in_array($this->status, [self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_CANCELLED], true);
        }

        if ($newStatus === self::STATUS_PAID) {
            if ($this->isPostPaid()) {
                return $this->status === self::STATUS_SHIPPED;
            }

            return $this->status === self::STATUS_PENDING_PAYMENT;
        }

        $allowed = [
            self::STATUS_PENDING_PAYMENT => $this->isPrePaid() ? [self::STATUS_PAID] : [],
            self::STATUS_PAID => $this->isPostPaid()
                ? [self::STATUS_DELIVERED]
                : [
                $this->useStockMode() ? self::STATUS_CHECKING_STOCK : self::STATUS_PROCURING,
                ],
            self::STATUS_CHECKING_STOCK => [self::STATUS_PICKING],
            self::STATUS_PICKING => $this->requiresPacking() ? [self::STATUS_REPACKING] : [self::STATUS_READY],
            self::STATUS_PROCURING => $this->requiresPacking() ? [self::STATUS_REPACKING] : [self::STATUS_READY],
            self::STATUS_REPACKING => $this->requiresPacking() ? [self::STATUS_READY] : [],
            self::STATUS_READY => [self::STATUS_SHIPPED],
            self::STATUS_SHIPPED => $this->isPostPaid() ? [self::STATUS_PAID] : [self::STATUS_DELIVERED],
        ];

        return in_array($newStatus, $allowed[$this->status] ?? [], true);
    }

    public static function calculateTotals(
        int|float $subtotal,
        string $discountType,
        int|float $discountValue,
        ?string $shippingType,
        int|float|null $shippingWeight,
        int|float|null $shippingDistance,
        int|float|null $shippingRate,
        int|float|null $packingFee,
        bool $includePpn,
        int|float|null $ppnRate
    ): array {
        $subtotal = max(0, (float) $subtotal);
        $discountValue = max(0, (float) $discountValue);
        $shippingType = $shippingType ?: self::SHIPPING_NONE;
        $shippingRate = max(0, (float) $shippingRate);
        $packingFee = max(0, (float) $packingFee);
        $ppnRate = max(0, (float) ($ppnRate ?? 0));

        $discountAmount = match ($discountType) {
            self::DISCOUNT_PERCENT => $subtotal * min($discountValue, 100) / 100,
            self::DISCOUNT_NOMINAL => min($discountValue, $subtotal),
            default => 0,
        };

        $afterDiscount = max(0, $subtotal - $discountAmount);
        $shippingCost = match ($shippingType) {
            self::SHIPPING_WEIGHT => max(0, (float) ($shippingWeight ?? 0)) * $shippingRate,
            self::SHIPPING_DISTANCE => max(0, (float) ($shippingDistance ?? 0)) * $shippingRate,
            self::SHIPPING_FLAT => $shippingRate,
            default => 0,
        };
        $taxableAmount = $afterDiscount + $shippingCost + $packingFee;
        $ppnAmount = $includePpn ? $taxableAmount * min($ppnRate, 100) / 100 : 0;
        $grandTotal = $taxableAmount + $ppnAmount;

        return [
            'discount_amount' => (int) round($discountAmount),
            'after_discount' => (int) round($afterDiscount),
            'shipping_cost' => (int) round($shippingCost),
            'ppn_rate' => $includePpn ? min($ppnRate, 100) : 0,
            'ppn_amount' => (int) round($ppnAmount),
            'grand_total' => (int) round($grandTotal),
        ];
    }

    public function recalculateTotal(): void
    {
        $this->subtotal = $this->items->sum('subtotal');

        $totals = self::calculateTotals(
            $this->subtotal,
            $this->discount_type,
            $this->discount_value,
            $this->shipping_type,
            $this->shipping_weight,
            $this->shipping_distance,
            $this->shipping_rate,
            $this->packing_fee,
            $this->include_ppn,
            $this->ppn_rate
        );

        $this->delivery_fee = $totals['shipping_cost'];
        $this->discount_amount = $totals['discount_amount'];
        $this->ppn_rate = $totals['ppn_rate'];
        $this->ppn_amount = $totals['ppn_amount'];
        $this->grand_total = $totals['grand_total'];
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

    public function scopePicking($query)
    {
        return $query->where('status', self::STATUS_PICKING);
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
