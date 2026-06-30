<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBonusRule;
use Illuminate\Support\Carbon;

class ProductPromoBundlingService
{
    public function resolvePromo(Product $product, int|float $quantity, ?Customer $customer = null, ?int $companyBranchId = null, ?Carbon $date = null): ?ProductBonusRule
    {
        return app(ProductBonusService::class)->resolveBonus($product, $quantity, $customer, $companyBranchId, $date);
    }

    public function preview(ProductBonusRule $rule): array
    {
        return [
            'promo_code' => $rule->promo_code,
            'promo_name' => $rule->promo_name,
            'promo_label' => $rule->promo_label,
            'bonus_label' => $rule->bonus_label,
            'bonus_product_id' => $rule->bonus_product_id,
            'bonus_quantity' => $rule->bonus_quantity,
        ];
    }
}
