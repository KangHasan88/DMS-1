<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'consignment_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'integer',
    ];

    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_TRANSFER = 'transfer';
    const PAYMENT_METHOD_CHEQUE = 'cheque';

    const PAYMENT_METHODS = [
        self::PAYMENT_METHOD_CASH => 'Tunai',
        self::PAYMENT_METHOD_TRANSFER => 'Transfer Bank',
        self::PAYMENT_METHOD_CHEQUE => 'Cheque',
    ];

    // ===================== RELATIONSHIPS =====================
    
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===================== ACCESSORS =====================
    
    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst($this->payment_method);
    }
}