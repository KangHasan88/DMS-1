<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductPriceRule;
use Illuminate\Support\Carbon;

class ProductPricingService
{
    public function resolvePrice(Product $product, ?Customer $customer = null, ?int $companyBranchId = null, ?Carbon $date = null): int
    {
        $date ??= now();
        $customerType = $customer?->customer_type;

        $rule = ProductPriceRule::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $date->toDateString());
            })
            ->where(function ($scope) use ($customer, $customerType, $companyBranchId) {
                $scope->where(function ($query) use ($customer, $companyBranchId) {
                    $query->where('customer_id', $customer?->id)
                        ->where('company_branch_id', $companyBranchId);
                })->orWhere(function ($query) use ($customer) {
                    $query->where('customer_id', $customer?->id)
                        ->whereNull('company_branch_id');
                })->orWhere(function ($query) use ($customerType, $companyBranchId) {
                    $query->where('customer_type', $customerType)
                        ->where('company_branch_id', $companyBranchId)
                        ->whereNull('customer_id');
                })->orWhere(function ($query) use ($customerType) {
                    $query->where('customer_type', $customerType)
                        ->whereNull('company_branch_id')
                        ->whereNull('customer_id');
                })->orWhere(function ($query) use ($companyBranchId) {
                    $query->whereNull('customer_id')
                        ->whereNull('customer_type')
                        ->where('company_branch_id', $companyBranchId);
                })->orWhere(function ($query) {
                    $query->whereNull('customer_id')
                        ->whereNull('customer_type')
                        ->whereNull('company_branch_id');
                });
            })
            ->orderByRaw(
                "CASE
                    WHEN customer_id IS NOT NULL AND company_branch_id IS NOT NULL THEN 1
                    WHEN customer_id IS NOT NULL THEN 2
                    WHEN customer_type IS NOT NULL AND company_branch_id IS NOT NULL THEN 3
                    WHEN customer_type IS NOT NULL THEN 4
                    WHEN company_branch_id IS NOT NULL THEN 5
                    ELSE 6
                END"
            )
            ->latest('starts_at')
            ->latest('id')
            ->first();

        return $rule ? $rule->price : (int) $product->price;
    }
}
