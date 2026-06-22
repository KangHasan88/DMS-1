<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyBranch extends Model
{
    protected $fillable = [
        'company_profile_id',
        'name',
        'code',
        'phone',
        'email',
        'address',
        'is_invoice_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_invoice_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function salesTerritories(): HasMany
    {
        return $this->hasMany(SalesTerritory::class);
    }

    public function customerSalesAssignments(): HasMany
    {
        return $this->hasMany(CustomerSalesAssignment::class);
    }

    public function deliveryZones(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryZone::class, 'delivery_zone_depots')
            ->withPivot(['priority', 'max_daily_orders', 'is_active'])
            ->withTimestamps();
    }

    public function toInvoiceBranch(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }
}
