<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnablePackageBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'returnable_package_id',
        'customer_id',
        'company_branch_id',
        'outstanding_quantity',
        'last_movement_at',
    ];

    protected $casts = [
        'outstanding_quantity' => 'integer',
        'last_movement_at' => 'datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(ReturnablePackage::class, 'returnable_package_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }
}
