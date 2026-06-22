<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_payment_id',
        'ap_invoice_id',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function supplierPayment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class);
    }

    public function apInvoice(): BelongsTo
    {
        return $this->belongsTo(ApInvoice::class);
    }
}
