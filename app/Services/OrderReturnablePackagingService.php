<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnablePackageMovement;
use Illuminate\Support\Collection;

class OrderReturnablePackagingService
{
    public function packagePlan(Order $order): Collection
    {
        $order->loadMissing('items.product.returnablePackage');

        return $order->items
            ->filter(function ($item) {
                return $item->product
                    && $item->product->hasReturnablePackaging()
                    && $item->product->returnable_package_default_flow === Product::PACKAGING_FLOW_RETURNABLE;
            })
            ->groupBy(fn ($item) => $item->product->returnable_package_id)
            ->map(function (Collection $items) {
                $firstItem = $items->first();
                $package = $firstItem->product->returnablePackage;
                $quantity = $items->sum(fn ($item) => $item->product->returnablePackageQuantityFor((int) $item->quantity));

                return [
                    'package' => $package,
                    'quantity' => (int) $quantity,
                    'items' => $items,
                ];
            })
            ->filter(fn (array $plan) => $plan['package'] && $plan['quantity'] > 0)
            ->values();
    }

    public function postDeliveredOrder(Order $order, ?int $createdBy = null): int
    {
        $order->loadMissing('user.customer', 'items.product.returnablePackage');
        $customer = $order->user?->customer;

        if (!$customer) {
            return 0;
        }

        $posted = 0;

        foreach ($this->packagePlan($order) as $plan) {
            $package = $plan['package'];

            $alreadyPosted = ReturnablePackageMovement::query()
                ->where('returnable_package_id', $package->id)
                ->where('customer_id', $customer->id)
                ->where('movement_type', ReturnablePackageMovement::TYPE_ISSUED)
                ->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->exists();

            if ($alreadyPosted) {
                continue;
            }

            ReturnablePackageMovement::recordMovement([
                'returnable_package_id' => $package->id,
                'customer_id' => $customer->id,
                'company_branch_id' => $order->company_branch_id,
                'movement_type' => ReturnablePackageMovement::TYPE_ISSUED,
                'movement_date' => now()->toDateString(),
                'quantity' => $plan['quantity'],
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'reference_number' => $order->order_number,
                'unit_value' => $package->replacement_value,
                'notes' => 'Auto dari pengiriman order ' . $order->order_number,
                'created_by' => $createdBy,
            ]);

            $posted++;
        }

        return $posted;
    }
}
