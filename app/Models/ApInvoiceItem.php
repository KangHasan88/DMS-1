<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ap_invoice_id',
        'purchase_order_item_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'line_total' => 'integer',
    ];

    public function apInvoice(): BelongsTo
    {
        return $this->belongsTo(ApInvoice::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
