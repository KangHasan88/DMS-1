<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTimeSlot extends Model
{
    protected $fillable = [
        'company_branch_id',
        'name',
        'start_time',
        'end_time',
        'period_label',
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

    public function getValueAttribute(): string
    {
        return substr($this->start_time, 0, 5) . '-' . substr($this->end_time, 0, 5);
    }

    public function getDisplayLabelAttribute(): string
    {
        $label = substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5);

        if ($this->period_label) {
            $label .= ' (' . $this->period_label . ')';
        }

        return $label;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('period_label', 'like', "%{$search}%")
                ->orWhere('start_time', 'like', "%{$search}%")
                ->orWhere('end_time', 'like', "%{$search}%");
        });
    }
}
