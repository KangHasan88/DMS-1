<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesTerritory extends Model
{
    protected $fillable = [
        'company_branch_id',
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

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function customerAssignments(): HasMany
    {
        return $this->hasMany(CustomerSalesAssignment::class);
    }

    public function activeCustomerAssignments(): HasMany
    {
        return $this->customerAssignments()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
            });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompanyBranch($query, ?int $branchId)
    {
        return $branchId ? $query->where('company_branch_id', $branchId) : $query;
    }
}
