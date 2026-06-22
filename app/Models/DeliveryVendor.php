<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryVendor extends Model
{
    public const TYPE_EXPEDITION = 'expedition';
    public const TYPE_INSTANT = 'instant';
    public const TYPE_TRUCKING = 'trucking';
    public const TYPE_CUSTOM = 'custom';

    public const PAYMENT_TERM_CASH = 'cash';
    public const PAYMENT_TERM_INVOICE = 'invoice';
    public const PAYMENT_TERM_WEEKLY = 'weekly';
    public const PAYMENT_TERM_MONTHLY = 'monthly';

    protected $fillable = [
        'company_branch_id',
        'name',
        'code',
        'vendor_type',
        'phone',
        'contact_person',
        'payment_term',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompanyBranch($query, ?int $companyBranchId)
    {
        return $companyBranchId
            ? $query->where(function ($query) use ($companyBranchId) {
                $query->whereNull('company_branch_id')
                    ->orWhere('company_branch_id', $companyBranchId);
            })
            : $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('contact_person', 'like', "%{$search}%");
        });
    }
}
