<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductDiscountRule;
use Illuminate\Support\Carbon;

class ProductDiscountService
{
    public function resolveItemDiscount(
        Product $product,
        int|float $unitPrice,
        int|float $quantity,
        ?Customer $customer = null,
        ?int $companyBranchId = null,
        ?Carbon $date = null
    ): array {
        $date ??= now();
        $quantity = max(0, (float) $quantity);
        $lineGross = max(0, (float) $unitPrice) * $quantity;

        if ($lineGross <= 0 || $quantity <= 0) {
            return ['amount' => 0, 'rule' => null];
        }

        $customerType = $customer?->customer_type;

        $rule = ProductDiscountRule::query()
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $date->toDateString());
            })
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($product) {
                $query->where('product_id', $product->id)
                    ->orWhereNull('product_id');
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
            ->orderByRaw('CASE WHEN product_id IS NOT NULL THEN 1 ELSE 2 END')
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
            ->latest('min_quantity')
            ->latest('starts_at')
            ->latest('id')
            ->first();

        if (!$rule) {
            return ['amount' => 0, 'rule' => null];
        }

        $amount = match ($rule->discount_type) {
            ProductDiscountRule::TYPE_PERCENT => $lineGross * min((float) $rule->discount_value, 100) / 100,
            ProductDiscountRule::TYPE_NOMINAL => min((float) $rule->discount_value, (float) $unitPrice) * $quantity,
            default => 0,
        };

        return [
            'amount' => (int) round(min($amount, $lineGross)),
            'rule' => $rule,
        ];
    }
}
