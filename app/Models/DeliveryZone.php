<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $fillable = [
        'company_profile_id',
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function depots(): BelongsToMany
    {
        return $this->belongsToMany(CompanyBranch::class, 'delivery_zone_depots')
            ->withPivot(['priority', 'max_daily_orders', 'is_active'])
            ->withTimestamps()
            ->orderByPivot('priority');
    }

    public function activeDepots(): BelongsToMany
    {
        return $this->depots()->wherePivot('is_active', true);
    }

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'delivery_zone_drivers', 'delivery_zone_id', 'driver_id')
            ->withTimestamps();
    }

    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryVehicle::class, 'delivery_zone_vehicles')
            ->withTimestamps();
    }

    public function customerAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
