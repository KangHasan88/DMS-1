<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        $orders = Order::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total_orders' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
            'delivered_orders' => Order::whereBetween('created_at', [$startDate, $endDate])->where('status', Order::STATUS_DELIVERED)->count(),
            'gross_sales' => Order::whereBetween('created_at', [$startDate, $endDate])->where('status', Order::STATUS_DELIVERED)->sum('grand_total'),
            'pending_orders' => Order::whereBetween('created_at', [$startDate, $endDate])->where('status', Order::STATUS_PENDING_PAYMENT)->count(),
        ];

        return view('reports.sales', compact('orders', 'summary', 'startDate', 'endDate'));
    }

    public function inventory(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        $salesWindowStart = now()->subDays(30)->startOfDay();

        $products = Product::with('unit', 'stock')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $productIds = $products->getCollection()->pluck('id');

        $salesVelocity = OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as sold_last_30_days'))
            ->whereIn('product_id', $productIds)
            ->whereHas('order', function ($query) use ($salesWindowStart) {
                $query->whereIn('status', [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])
                    ->where('created_at', '>=', $salesWindowStart);
            })
            ->groupBy('product_id')
            ->pluck('sold_last_30_days', 'product_id');

        $products->getCollection()->transform(function (Product $product) use ($salesVelocity) {
            $quantity = $product->stock?->quantity ?? 0;
            $soldLast30Days = (int) ($salesVelocity[$product->id] ?? 0);
            $weeklySalesAverage = $soldLast30Days > 0 ? $soldLast30Days / (30 / 7) : 0;
            $weekCover = $weeklySalesAverage > 0 ? round($quantity / $weeklySalesAverage, 1) : null;

            $product->sold_last_30_days = $soldLast30Days;
            $product->weekly_sales_average = round($weeklySalesAverage, 1);
            $product->week_cover = $weekCover;
            $product->inventory_signal = $this->inventorySignal($quantity, $soldLast30Days, $weekCover);

            return $product;
        });

        $allProductIds = Product::pluck('id');
        $allSalesVelocity = OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as sold_last_30_days'))
            ->whereIn('product_id', $allProductIds)
            ->whereHas('order', function ($query) use ($salesWindowStart) {
                $query->whereIn('status', [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])
                    ->where('created_at', '>=', $salesWindowStart);
            })
            ->groupBy('product_id')
            ->pluck('sold_last_30_days', 'product_id');

        $inventorySignals = Product::with('stock')->get()
            ->map(function (Product $product) use ($allSalesVelocity) {
                $quantity = $product->stock?->quantity ?? 0;
                $soldLast30Days = (int) ($allSalesVelocity[$product->id] ?? 0);
                $weeklySalesAverage = $soldLast30Days > 0 ? $soldLast30Days / (30 / 7) : 0;
                $weekCover = $weeklySalesAverage > 0 ? round($quantity / $weeklySalesAverage, 1) : null;

                return $this->inventorySignal($quantity, $soldLast30Days, $weekCover)['type'];
            });

        $summary = [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'stock_in' => StockMovement::whereBetween('created_at', [$startDate, $endDate])->where('type', StockMovement::TYPE_IN)->sum('quantity'),
            'stock_out' => StockMovement::whereBetween('created_at', [$startDate, $endDate])->where('type', StockMovement::TYPE_OUT)->sum('quantity'),
            'slow_moving' => $inventorySignals->filter(fn (string $type) => $type === 'slow')->count(),
            'overstock' => $inventorySignals->filter(fn (string $type) => $type === 'overstock')->count(),
        ];

        return view('reports.inventory', compact('products', 'summary', 'startDate', 'endDate'));
    }

    public function delivery(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        $deliveries = Delivery::with('order.user', 'kurir')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total_deliveries' => Delivery::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed' => Delivery::whereBetween('created_at', [$startDate, $endDate])->where('status', Delivery::STATUS_COMPLETED)->count(),
            'in_progress' => Delivery::whereBetween('created_at', [$startDate, $endDate])->whereIn('status', [Delivery::STATUS_ASSIGNED, Delivery::STATUS_PICKED_UP, Delivery::STATUS_IN_TRANSIT])->count(),
            'today' => Delivery::whereDate('created_at', today())->count(),
        ];

        return view('reports.delivery', compact('deliveries', 'summary', 'startDate', 'endDate'));
    }

    public function financial(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        $orders = Order::whereBetween('created_at', [$startDate, $endDate]);
        $delivered = (clone $orders)->where('status', Order::STATUS_DELIVERED);
        $deliveredOrderIds = (clone $delivered)->pluck('id');
        $actualShippingCost = Delivery::whereIn('order_id', $deliveredOrderIds)->sum('actual_shipping_cost');
        $customerShippingRevenue = (clone $delivered)->sum('delivery_fee');

        $summary = [
            'revenue' => (clone $delivered)->sum('grand_total'),
            'subtotal' => (clone $delivered)->sum('subtotal'),
            'discount' => (clone $delivered)->sum('discount_amount'),
            'delivery_fee' => $customerShippingRevenue,
            'actual_shipping_cost' => $actualShippingCost,
            'shipping_margin' => $customerShippingRevenue - $actualShippingCost,
            'packing_fee' => (clone $delivered)->sum('packing_fee'),
            'tax' => (clone $delivered)->sum('ppn_amount'),
            'paid_orders' => (clone $orders)->whereNotNull('paid_at')->count(),
            'unpaid_orders' => (clone $orders)->whereNull('paid_at')->where('status', '!=', Order::STATUS_CANCELLED)->count(),
        ];

        $byPaymentMethod = (clone $delivered)
            ->select('payment_method', DB::raw('COUNT(*) as total_orders'), DB::raw('SUM(grand_total) as total_amount'))
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get();

        return view('reports.financial', compact('summary', 'byPaymentMethod', 'startDate', 'endDate'));
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['sales', 'inventory', 'delivery', 'financial'], true), 404);

        [$startDate, $endDate] = $this->dateRange($request);

        return response()->streamDownload(function () use ($type, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', ucfirst($type)]);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, ['Start Date', $startDate->toDateString()]);
            fputcsv($handle, ['End Date', $endDate->toDateString()]);
            fclose($handle);
        }, $type . '-report-' . now()->format('Ymd-His') . '.csv');
    }

    private function dateRange(Request $request): array
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->filled('start_date')
            ? $request->date('start_date')->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $endDate = $request->filled('end_date')
            ? $request->date('end_date')->endOfDay()
            : now()->endOfDay();

        return [$startDate, $endDate];
    }

    private function inventorySignal(int $quantity, int $soldLast30Days, ?float $weekCover): array
    {
        if ($quantity <= 0) {
            return ['type' => 'out', 'label' => 'Stok Habis', 'class' => 'danger'];
        }

        if ($soldLast30Days === 0) {
            return ['type' => 'slow', 'label' => 'Belum Bergerak 30 Hari', 'class' => 'warning'];
        }

        if ($weekCover !== null && $weekCover <= 1) {
            return ['type' => 'reorder', 'label' => 'Perlu Reorder', 'class' => 'danger'];
        }

        if ($weekCover !== null && $weekCover > 8) {
            return ['type' => 'overstock', 'label' => 'Stok Berlebih', 'class' => 'warning'];
        }

        return ['type' => 'healthy', 'label' => 'Sehat', 'class' => 'success'];
    }
}
