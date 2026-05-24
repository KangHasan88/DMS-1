<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'kurir_id',
        'status',
        'assigned_at',
        'picked_up_at',
        'in_transit_at',
        'completed_at',
        'proof_image',
        'notes',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'in_transit_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_ASSIGNED = 'assigned';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COMPLETED = 'completed';

    const STATUS_LIST = [
        self::STATUS_ASSIGNED => 'Assigned',
        self::STATUS_PICKED_UP => 'Picked Up',
        self::STATUS_IN_TRANSIT => 'In Transit',
        self::STATUS_COMPLETED => 'Completed',
    ];

    const STATUS_COLORS = [
        self::STATUS_ASSIGNED => 'warning',
        self::STATUS_PICKED_UP => 'info',
        self::STATUS_IN_TRANSIT => 'info',
        self::STATUS_COMPLETED => 'success',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function kurir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kurir_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function markAsPickedUp(): void
    {
        $this->status = self::STATUS_PICKED_UP;
        $this->picked_up_at = now();
        $this->save();
    }

    public function markAsInTransit(): void
    {
        $this->status = self::STATUS_IN_TRANSIT;
        $this->in_transit_at = now();
        $this->save();
    }

    public function markAsCompleted(?string $proofImage = null): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        if ($proofImage) {
            $this->proof_image = $proofImage;
        }
        $this->save();
    }
}