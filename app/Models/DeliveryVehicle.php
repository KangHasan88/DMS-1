<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryVehicle extends Model
{
    protected $fillable = [
        'company_branch_id',
        'code',
        'name',
        'vehicle_type',
        'plate_number',
        'capacity',
        'status',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const TYPE_MOTORCYCLE = 'motorcycle';
    const TYPE_PICKUP = 'pickup';
    const TYPE_BOX_CAR = 'box_car';
    const TYPE_TRUCK = 'truck';
    const TYPE_OTHER = 'other';

    const TYPE_LIST = [
        self::TYPE_MOTORCYCLE => 'Motor',
        self::TYPE_PICKUP => 'Pickup',
        self::TYPE_BOX_CAR => 'Mobil Box',
        self::TYPE_TRUCK => 'Truk',
        self::TYPE_OTHER => 'Lainnya',
    ];

    const STATUS_AVAILABLE = 'available';
    const STATUS_IN_USE = 'in_use';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_INACTIVE = 'inactive';

    const STATUS_LIST = [
        self::STATUS_AVAILABLE => 'Tersedia',
        self::STATUS_IN_USE => 'Dipakai',
        self::STATUS_MAINTENANCE => 'Perbaikan',
        self::STATUS_INACTIVE => 'Tidak Aktif',
    ];

    const STATUS_COLORS = [
        self::STATUS_AVAILABLE => 'success',
        self::STATUS_IN_USE => 'info',
        self::STATUS_MAINTENANCE => 'warning',
        self::STATUS_INACTIVE => 'secondary',
    ];

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'delivery_vehicle_id');
    }

    public function driverAssignments(): HasMany
    {
        return $this->hasMany(DriverVehicleAssignment::class, 'delivery_vehicle_id');
    }

    public function deliveryZones(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryZone::class, 'delivery_zone_vehicles')
            ->withTimestamps();
    }

    public function activeDriverAssignment()
    {
        return $this->hasOne(DriverVehicleAssignment::class, 'delivery_vehicle_id')
            ->whereNull('ended_at')
            ->latestOfMany('started_at');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LIST[$this->vehicle_type] ?? ucfirst(str_replace('_', ' ', (string) $this->vehicle_type));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LIST[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->active()->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeForCompanyBranch($query, ?int $companyBranchId)
    {
        return $query->where(function ($query) use ($companyBranchId) {
            $query->whereNull('company_branch_id');

            if ($companyBranchId) {
                $query->orWhere('company_branch_id', $companyBranchId);
            }
        });
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($query) use ($search) {
            $query->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('plate_number', 'like', "%{$search}%")
                ->orWhere('vehicle_type', 'like', "%{$search}%");
        });
    }
}
