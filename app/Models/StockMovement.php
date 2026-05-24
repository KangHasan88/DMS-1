<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'order_id',
        'source_type',
        'source_id',
        'type',
        'quantity',
        'before_quantity',
        'after_quantity',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'before_quantity' => 'integer',
        'after_quantity' => 'integer',
    ];

    // ===================== TYPE CONSTANTS =====================
    
    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_ADJUSTMENT = 'adjustment';

    const TYPES = [
        self::TYPE_IN => 'Stock Masuk',
        self::TYPE_OUT => 'Stock Keluar',
        self::TYPE_ADJUSTMENT => 'Penyesuaian Stock',
    ];

    // ===================== SOURCE TYPE CONSTANTS =====================
    
    // Inbound Sources
    const SOURCE_PURCHASE_ORDER = 'purchase_order';
    const SOURCE_DIRECT_PURCHASE = 'direct_purchase';
    const SOURCE_FOC = 'foc';
    const SOURCE_CONSIGNMENT = 'consignment';
    const SOURCE_CONSIGNMENT_RETURN = 'consignment_return';
    const SOURCE_CONSIGNMENT_SALE = 'consignment_sale';
    
    // Outbound Sources
    const SOURCE_ORDER = 'order';
    const SOURCE_FOC_OUT = 'foc_out';
    const SOURCE_RETURN_OUT = 'return_out';
    const SOURCE_ADJUSTMENT = 'adjustment';

    const SOURCE_TYPES = [
        // Inbound
        self::SOURCE_PURCHASE_ORDER => 'Purchase Order',
        self::SOURCE_DIRECT_PURCHASE => 'Direct Purchase',
        self::SOURCE_FOC => 'FOC (Bonus)',
        self::SOURCE_CONSIGNMENT => 'Consignment Masuk',
        self::SOURCE_CONSIGNMENT_RETURN => 'Consignment Return',
        self::SOURCE_CONSIGNMENT_SALE => 'Consignment Terjual',
        // Outbound
        self::SOURCE_ORDER => 'Customer Order',
        self::SOURCE_FOC_OUT => 'FOC Out (Hadiah)',
        self::SOURCE_RETURN_OUT => 'Return Out (Retur)',
        self::SOURCE_ADJUSTMENT => 'Penyesuaian Stok',
    ];

    // ===================== RELATIONSHIPS =====================
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Simple belongsTo relationships (no where condition)
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'source_id');
    }

    public function directPurchase()
    {
        return $this->belongsTo(DirectPurchase::class, 'source_id');
    }

    public function consignment()
    {
        return $this->belongsTo(Consignment::class, 'source_id');
    }

    public function outboundFoc()
    {
        return $this->belongsTo(OutboundFoc::class, 'source_id');
    }

    public function outboundReturn()
    {
        return $this->belongsTo(OutboundReturn::class, 'source_id');
    }

    // ===================== ACCESSORS =====================
    
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getSourceLabelAttribute(): string
    {
        // Inbound Sources
        if ($this->source_type === self::SOURCE_PURCHASE_ORDER && $this->purchaseOrder) {
            return 'PO #' . $this->purchaseOrder->po_number;
        }
        if ($this->source_type === self::SOURCE_DIRECT_PURCHASE && $this->directPurchase) {
            return 'Direct Purchase #' . $this->directPurchase->invoice_number;
        }
        if ($this->source_type === self::SOURCE_FOC) {
            return 'FOC (Free of Charge)';
        }
        if (in_array($this->source_type, [self::SOURCE_CONSIGNMENT, self::SOURCE_CONSIGNMENT_RETURN, self::SOURCE_CONSIGNMENT_SALE]) && $this->consignment) {
            $prefix = [
                self::SOURCE_CONSIGNMENT => 'CN Masuk',
                self::SOURCE_CONSIGNMENT_RETURN => 'CN Return',
                self::SOURCE_CONSIGNMENT_SALE => 'CN Terjual',
            ];
            return $prefix[$this->source_type] . ' #' . $this->consignment->cn_number;
        }
        
        // Outbound Sources
        if ($this->source_type === self::SOURCE_ORDER && $this->order) {
            return 'Order #' . $this->order->order_number;
        }
        if ($this->source_type === self::SOURCE_FOC_OUT && $this->outboundFoc) {
            return 'FOC #' . $this->outboundFoc->foc_number;
        }
        if ($this->source_type === self::SOURCE_RETURN_OUT && $this->outboundReturn) {
            return 'Return #' . $this->outboundReturn->return_number;
        }
        if ($this->source_type === self::SOURCE_ADJUSTMENT) {
            return 'Penyesuaian';
        }
        
        return '-';
    }

    public function getFormattedQuantityAttribute(): string
    {
        if ($this->type === self::TYPE_IN) {
            return '+' . number_format($this->quantity);
        }
        if ($this->type === self::TYPE_OUT) {
            return '-' . number_format($this->quantity);
        }
        return number_format($this->quantity);
    }

    public function getSourceBadgeAttribute(): string
    {
        $badges = [
            // Inbound
            self::SOURCE_PURCHASE_ORDER => 'primary',
            self::SOURCE_DIRECT_PURCHASE => 'info',
            self::SOURCE_FOC => 'success',
            self::SOURCE_CONSIGNMENT => 'success',
            self::SOURCE_CONSIGNMENT_RETURN => 'warning',
            self::SOURCE_CONSIGNMENT_SALE => 'info',
            // Outbound
            self::SOURCE_ORDER => 'danger',
            self::SOURCE_FOC_OUT => 'success',
            self::SOURCE_RETURN_OUT => 'warning',
            self::SOURCE_ADJUSTMENT => 'secondary',
        ];
        
        return $badges[$this->source_type] ?? 'secondary';
    }
}