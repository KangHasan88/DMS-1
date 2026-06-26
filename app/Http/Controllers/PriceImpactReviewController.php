<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ApprovalRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PriceImpactReviewController extends Controller
{
    public function index(Request $request)
    {
        $targetMargin = max(1, min(80, (float) $request->input('target_margin', 25)));
        $costIncreaseThreshold = max(0, min(100, (float) $request->input('cost_increase_threshold', 5)));
        $mode = $request->input('mode', 'review_only');
        $search = trim((string) $request->input('search', ''));

        $products = Product::query()
            ->with(['unit'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $rows = $products->map(function (Product $product) use ($targetMargin, $costIncreaseThreshold) {
            $latestPurchaseItem = PurchaseOrderItem::query()
                ->with(['purchaseOrder.supplier'])
                ->where('product_id', $product->id)
                ->whereHas('purchaseOrder', function ($query) {
                    $query->where('status', '!=', PurchaseOrder::STATUS_CANCELLED);
                })
                ->latest('id')
                ->first();

            $masterCost = (int) $product->base_price;
            $sellingPrice = (int) $product->price;
            $latestPurchasePrice = (int) ($latestPurchaseItem?->price ?? 0);
            $effectiveCost = $latestPurchasePrice > 0 ? $latestPurchasePrice : $masterCost;
            $costChangePercent = $masterCost > 0 && $latestPurchasePrice > 0
                ? round((($latestPurchasePrice - $masterCost) / $masterCost) * 100, 2)
                : null;
            $currentMarginPercent = $sellingPrice > 0
                ? round((($sellingPrice - $masterCost) / $sellingPrice) * 100, 2)
                : null;
            $projectedMarginPercent = $sellingPrice > 0 && $effectiveCost > 0
                ? round((($sellingPrice - $effectiveCost) / $sellingPrice) * 100, 2)
                : null;
            $recommendedPrice = $effectiveCost > 0 && $targetMargin < 100
                ? (int) ceil(($effectiveCost / (1 - ($targetMargin / 100))) / 500) * 500
                : $sellingPrice;
            $recommendedIncrease = max(0, $recommendedPrice - $sellingPrice);
            $needsReview = $latestPurchasePrice > 0 && (
                ($costChangePercent !== null && $costChangePercent >= $costIncreaseThreshold)
                || ($projectedMarginPercent !== null && $projectedMarginPercent < $targetMargin)
            );

            return [
                'product' => $product,
                'latest_purchase_item' => $latestPurchaseItem,
                'master_cost' => $masterCost,
                'selling_price' => $sellingPrice,
                'latest_purchase_price' => $latestPurchasePrice,
                'cost_change_percent' => $costChangePercent,
                'current_margin_percent' => $currentMarginPercent,
                'projected_margin_percent' => $projectedMarginPercent,
                'recommended_price' => $recommendedPrice,
                'recommended_increase' => $recommendedIncrease,
                'needs_review' => $needsReview,
            ];
        });

        if ($mode === 'review_only') {
            $rows = $rows->filter(fn (array $row) => $row['needs_review'])->values();
        }

        $allRows = $products->map(function (Product $product) use ($targetMargin, $costIncreaseThreshold) {
            $latestPurchaseItem = PurchaseOrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('purchaseOrder', fn ($query) => $query->where('status', '!=', PurchaseOrder::STATUS_CANCELLED))
                ->latest('id')
                ->first();

            $masterCost = (int) $product->base_price;
            $sellingPrice = (int) $product->price;
            $latestPurchasePrice = (int) ($latestPurchaseItem?->price ?? 0);
            $projectedMarginPercent = $sellingPrice > 0 && ($latestPurchasePrice ?: $masterCost) > 0
                ? round((($sellingPrice - ($latestPurchasePrice ?: $masterCost)) / $sellingPrice) * 100, 2)
                : null;
            $costChangePercent = $masterCost > 0 && $latestPurchasePrice > 0
                ? round((($latestPurchasePrice - $masterCost) / $masterCost) * 100, 2)
                : null;

            return [
                'has_purchase' => $latestPurchasePrice > 0,
                'needs_review' => $latestPurchasePrice > 0 && (
                    ($costChangePercent !== null && $costChangePercent >= $costIncreaseThreshold)
                    || ($projectedMarginPercent !== null && $projectedMarginPercent < $targetMargin)
                ),
                'projected_margin_percent' => $projectedMarginPercent,
            ];
        });

        $purchasedRows = $allRows->filter(fn (array $row) => $row['has_purchase']);
        $stats = [
            'products_with_purchase' => $purchasedRows->count(),
            'needs_review' => $allRows->filter(fn (array $row) => $row['needs_review'])->count(),
            'average_projected_margin' => $purchasedRows->pluck('projected_margin_percent')->filter(fn ($value) => $value !== null)->avg(),
        ];

        return view('price-impact-review.index', compact('rows', 'targetMargin', 'costIncreaseThreshold', 'mode', 'search', 'stats'));
    }

    public function apply(Request $request, Product $product, ApprovalWorkflowService $approvalWorkflow)
    {
        $validated = $request->validate([
            'new_base_price' => ['required', 'integer', 'min:0'],
            'new_price' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $pendingApproval = ApprovalRequest::query()
            ->where('approval_type', ApprovalRequest::TYPE_PRICE_CHANGE)
            ->where('approvable_type', Product::class)
            ->where('approvable_id', $product->id)
            ->where('status', ApprovalRequest::STATUS_PENDING)
            ->exists();

        if ($pendingApproval) {
            throw ValidationException::withMessages([
                'approval' => 'Masih ada approval perubahan harga yang menunggu untuk produk ini.',
            ]);
        }

        $oldBasePrice = (int) $product->base_price;
        $oldPrice = (int) $product->price;
        $newBasePrice = (int) $validated['new_base_price'];
        $newPrice = (int) $validated['new_price'];
        $reason = $validated['reason'] ?: 'Review dampak kenaikan harga beli';

        $approvalWorkflow->request([
            'approval_type' => ApprovalRequest::TYPE_PRICE_CHANGE,
            'title' => 'Perubahan harga ' . $product->name,
            'description' => 'Review dan setujui perubahan harga master produk sebelum efektif ke order baru.',
            'request_note' => $reason,
            'payload' => [
                'product' => $product->name,
                'old_base_price' => $oldBasePrice,
                'new_base_price' => $newBasePrice,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'reason' => $reason,
            ],
        ], $product);

        return back()->with('success', 'Permintaan approval perubahan harga berhasil dibuat. Harga baru akan efektif setelah disetujui.');
    }
}
