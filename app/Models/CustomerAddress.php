<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasFactory;

    public const TYPE_INVOICE = 'invoice';
    public const TYPE_SHIPPING = 'shipping';
    public const TYPE_BOTH = 'both';

    protected $fillable = [
        'customer_id',
        'label',
        'type',
        'address',
        'recipient_name',
        'recipient_phone',
        'latitude',
        'longitude',
        'delivery_zone_id',
        'coverage_verified_at',
        'coverage_verified_by',
        'is_default_invoice',
        'is_default_shipping',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_default_invoice' => 'boolean',
        'is_default_shipping' => 'boolean',
        'is_active' => 'boolean',
        'coverage_verified_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function coverageVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coverage_verified_by');
    }

    public function supportsInvoice(): bool
    {
        return in_array($this->type, [self::TYPE_INVOICE, self::TYPE_BOTH], true);
    }

    public function supportsShipping(): bool
    {
        return in_array($this->type, [self::TYPE_SHIPPING, self::TYPE_BOTH], true);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_INVOICE => 'Invoice / Dokumen',
            self::TYPE_SHIPPING => 'Pengiriman',
            self::TYPE_BOTH => 'Invoice & Pengiriman',
            default => $this->type,
        };
    }
}
