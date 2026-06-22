<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_method',
        'delivery_vendor_id',
        'kurir_id',
        'delivery_vehicle_id',
        'vehicle_override_reason',
        'status',
        'assigned_at',
        'picked_up_at',
        'in_transit_at',
        'completed_at',
        'tracking_code',
        'actual_shipping_cost',
        'shipping_cost_status',
        'vendor_invoice_number',
        'latitude',
        'longitude',
        'proof_image',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'in_transit_at' => 'datetime',
        'completed_at' => 'datetime',
        'actual_shipping_cost' => 'integer',
    ];

    const METHOD_INTERNAL = 'internal';
    const METHOD_EXPEDITION = 'expedition';

    const METHOD_LIST = [
        self::METHOD_INTERNAL => 'Internal',
        self::METHOD_EXPEDITION => 'Ekspedisi',
    ];

    const COST_NOT_APPLICABLE = 'not_applicable';
    const COST_UNBILLED = 'unbilled';
    const COST_BILLED = 'billed';
    const COST_PAID = 'paid';

    const COST_STATUS_LIST = [
        self::COST_NOT_APPLICABLE => 'Tidak Berlaku',
        self::COST_UNBILLED => 'Belum Ditagih',
        self::COST_BILLED => 'Sudah Ditagih',
        self::COST_PAID => 'Sudah Dibayar',
    ];

    const STATUS_ASSIGNED = 'assigned';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LIST = [
        self::STATUS_ASSIGNED => 'Ditugaskan',
        self::STATUS_PICKED_UP => 'Barang Diambil',
        self::STATUS_IN_TRANSIT => 'Dalam Pengiriman',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    const STATUS_COLORS = [
        self::STATUS_ASSIGNED => 'info',
        self::STATUS_PICKED_UP => 'warning',
        self::STATUS_IN_TRANSIT => 'primary',
        self::STATUS_COMPLETED => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    protected $appends = ['status_label', 'status_color'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function kurir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kurir_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(DeliveryVendor::class, 'delivery_vendor_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(DeliveryVehicle::class, 'delivery_vehicle_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function usesInternalDelivery(): bool
    {
        return $this->delivery_method === self::METHOD_INTERNAL;
    }

    public function usesExpedition(): bool
    {
        return $this->delivery_method === self::METHOD_EXPEDITION;
    }

    public function getDeliveryMethodLabelAttribute(): string
    {
        return self::METHOD_LIST[$this->delivery_method] ?? 'Internal';
    }

    public function getShippingCostStatusLabelAttribute(): string
    {
        return self::COST_STATUS_LIST[$this->shipping_cost_status] ?? '-';
    }

    public function shippingMargin(): int
    {
        return (int) ($this->order?->delivery_fee ?? 0) - (int) ($this->actual_shipping_cost ?? 0);
    }
}
