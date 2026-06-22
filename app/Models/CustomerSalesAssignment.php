<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSalesAssignment extends Model
{
    public const TYPE_PERMANENT = 'permanent';
    public const TYPE_TEMPORARY = 'temporary';

    protected $fillable = [
        'customer_id',
        'salesperson_id',
        'sales_territory_id',
        'company_branch_id',
        'start_date',
        'end_date',
        'assignment_type',
        'is_active',
        'notes',
        'assigned_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function salesTerritory(): BelongsTo
    {
        return $this->belongsTo(SalesTerritory::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
            });
    }
}
