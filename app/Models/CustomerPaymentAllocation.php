<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_payment_id',
        'ar_invoice_id',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function customerPayment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class);
    }

    public function arInvoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class);
    }
}
