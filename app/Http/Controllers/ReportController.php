<?php

namespace App\Http\Controllers;

use App\Models\ArInvoice;
use App\Models\ApInvoice;
use App\Models\CompanyBranch;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supplier;
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

    public function arAging(Request $request)
    {
        $request->validate([
            'as_of_date' => ['nullable', 'date'],
            'company_branch_id' => ['nullable', 'exists:company_branches,id'],
        ]);

        $asOfDate = $request->filled('as_of_date')
            ? $request->date('as_of_date')->endOfDay()
            : now()->endOfDay();

        $branchScopeId = auth()->user()?->scopedCompanyBranchId();
        $canFilterBranches = !$branchScopeId;

        $baseQuery = ArInvoice::with(['order', 'customer', 'customerUser', 'companyBranch'])
            ->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', [ArInvoice::STATUS_PAID, ArInvoice::STATUS_VOID])
            ->when($branchScopeId, fn ($query) => $query->where('company_branch_id', $branchScopeId))
            ->when(!$branchScopeId && $request->filled('company_branch_id'), fn ($query) => $query->where('company_branch_id', $request->company_branch_id));

        $allOpenInvoices = (clone $baseQuery)->get();
        $bucketSummary = $this->emptyAgingBuckets();

        $overdueInvoices = 0;
        $overdueAmount = 0;

        foreach ($allOpenInvoices as $invoice) {
            $bucket = $this->arAgingBucket($invoice, $asOfDate);
            $bucketSummary[$bucket['key']]['amount'] += (int) $invoice->outstanding_amount;
            $bucketSummary[$bucket['key']]['count']++;

            if ($bucket['key'] !== 'current') {
                $overdueInvoices++;
                $overdueAmount += (int) $invoice->outstanding_amount;
            }
        }

        $summary = [
            'open_invoices' => $allOpenInvoices->count(),
            'total_outstanding' => $allOpenInvoices->sum('outstanding_amount'),
            'overdue_invoices' => $overdueInvoices,
            'overdue_amount' => $overdueAmount,
        ];

        $invoices = (clone $baseQuery)
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        $invoices->getCollection()->transform(function (ArInvoice $invoice) use ($asOfDate) {
            $bucket = $this->arAgingBucket($invoice, $asOfDate);
            $invoice->aging_bucket = $bucket['label'];
            $invoice->aging_badge = $bucket['badge'];
            $invoice->days_overdue = $bucket['days_overdue'];

            return $invoice;
        });

        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();

        return view('reports.ar-aging', compact(
            'asOfDate',
            'summary',
            'bucketSummary',
            'invoices',
            'companyBranches',
            'canFilterBranches'
        ));
    }

    public function apAging(Request $request)
    {
        $request->validate([
            'as_of_date' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);

        $asOfDate = $request->filled('as_of_date')
            ? $request->date('as_of_date')->endOfDay()
            : now()->endOfDay();

        $baseQuery = ApInvoice::with(['purchaseOrder', 'supplier'])
            ->forUserBranch()
            ->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', [ApInvoice::STATUS_PAID, ApInvoice::STATUS_VOID])
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->supplier_id));

        $allOpenInvoices = (clone $baseQuery)->get();
        $bucketSummary = $this->emptyAgingBuckets();
        $overdueInvoices = 0;
        $overdueAmount = 0;

        foreach ($allOpenInvoices as $invoice) {
            $bucket = $this->apAgingBucket($invoice, $asOfDate);
            $bucketSummary[$bucket['key']]['amount'] += (int) $invoice->outstanding_amount;
            $bucketSummary[$bucket['key']]['count']++;

            if ($bucket['key'] !== 'current') {
                $overdueInvoices++;
                $overdueAmount += (int) $invoice->outstanding_amount;
            }
        }

        $summary = [
            'open_invoices' => $allOpenInvoices->count(),
            'total_outstanding' => $allOpenInvoices->sum('outstanding_amount'),
            'overdue_invoices' => $overdueInvoices,
            'overdue_amount' => $overdueAmount,
        ];

        $invoices = (clone $baseQuery)
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        $invoices->getCollection()->transform(function (ApInvoice $invoice) use ($asOfDate) {
            $bucket = $this->apAgingBucket($invoice, $asOfDate);
            $invoice->aging_bucket = $bucket['label'];
            $invoice->aging_badge = $bucket['badge'];
            $invoice->days_overdue = $bucket['days_overdue'];

            return $invoice;
        });

        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('reports.ap-aging', compact(
            'asOfDate',
            'summary',
            'bucketSummary',
            'invoices',
            'suppliers'
        ));
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['sales', 'inventory', 'delivery', 'financial', 'ar-aging', 'ap-aging'], true), 404);

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

    private function emptyAgingBuckets(): array
    {
        return [
            'current' => ['label' => 'Belum Jatuh Tempo', 'amount' => 0, 'count' => 0, 'badge' => 'info'],
            '1_30' => ['label' => '1-30 Hari', 'amount' => 0, 'count' => 0, 'badge' => 'warning'],
            '31_60' => ['label' => '31-60 Hari', 'amount' => 0, 'count' => 0, 'badge' => 'warning'],
            '61_90' => ['label' => '61-90 Hari', 'amount' => 0, 'count' => 0, 'badge' => 'danger'],
            'over_90' => ['label' => '>90 Hari', 'amount' => 0, 'count' => 0, 'badge' => 'danger'],
        ];
    }

    private function arAgingBucket(ArInvoice $invoice, $asOfDate): array
    {
        if (!$invoice->due_date || $invoice->due_date->greaterThanOrEqualTo($asOfDate->copy()->startOfDay())) {
            return ['key' => 'current', 'label' => 'Belum Jatuh Tempo', 'badge' => 'info', 'days_overdue' => 0];
        }

        $daysOverdue = (int) $invoice->due_date->diffInDays($asOfDate);

        if ($daysOverdue <= 30) {
            return ['key' => '1_30', 'label' => '1-30 Hari', 'badge' => 'warning', 'days_overdue' => $daysOverdue];
        }

        if ($daysOverdue <= 60) {
            return ['key' => '31_60', 'label' => '31-60 Hari', 'badge' => 'warning', 'days_overdue' => $daysOverdue];
        }

        if ($daysOverdue <= 90) {
            return ['key' => '61_90', 'label' => '61-90 Hari', 'badge' => 'danger', 'days_overdue' => $daysOverdue];
        }

        return ['key' => 'over_90', 'label' => '>90 Hari', 'badge' => 'danger', 'days_overdue' => $daysOverdue];
    }

    private function apAgingBucket(ApInvoice $invoice, $asOfDate): array
    {
        if (!$invoice->due_date || $invoice->due_date->greaterThanOrEqualTo($asOfDate->copy()->startOfDay())) {
            return ['key' => 'current', 'label' => 'Belum Jatuh Tempo', 'badge' => 'info', 'days_overdue' => 0];
        }

        $daysOverdue = (int) $invoice->due_date->diffInDays($asOfDate);

        if ($daysOverdue <= 30) {
            return ['key' => '1_30', 'label' => '1-30 Hari', 'badge' => 'warning', 'days_overdue' => $daysOverdue];
        }

        if ($daysOverdue <= 60) {
            return ['key' => '31_60', 'label' => '31-60 Hari', 'badge' => 'warning', 'days_overdue' => $daysOverdue];
        }

        if ($daysOverdue <= 90) {
            return ['key' => '61_90', 'label' => '61-90 Hari', 'badge' => 'danger', 'days_overdue' => $daysOverdue];
        }

        return ['key' => 'over_90', 'label' => '>90 Hari', 'badge' => 'danger', 'days_overdue' => $daysOverdue];
    }
}
