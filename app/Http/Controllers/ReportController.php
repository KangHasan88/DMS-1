<?php

namespace App\Http\Controllers;

use App\Models\ArInvoice;
use App\Models\ApInvoice;
use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\Delivery;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPrincipal;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
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

        $request->validate([
            'principal_id' => ['nullable', 'exists:product_principals,id'],
        ]);

        $selectedPrincipalId = $request->input('principal_id');
        $principalOptions = ProductPrincipal::active()->orderBy('sort_order')->orderBy('name')->get();

        $ordersQuery = Order::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($selectedPrincipalId, function ($query) use ($selectedPrincipalId) {
                $query->whereHas('items.product', fn ($query) => $query->where('principal_id', $selectedPrincipalId));
            });

        $orders = (clone $ordersQuery)->latest()->paginate(20)->withQueryString();

        $grossSales = $selectedPrincipalId
            ? OrderItem::query()
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.principal_id', $selectedPrincipalId)
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->where('orders.status', Order::STATUS_DELIVERED)
                ->sum('order_items.subtotal')
            : (clone $ordersQuery)->where('status', Order::STATUS_DELIVERED)->sum('grand_total');

        $summary = [
            'total_orders' => (clone $ordersQuery)->count(),
            'delivered_orders' => (clone $ordersQuery)->where('status', Order::STATUS_DELIVERED)->count(),
            'gross_sales' => $grossSales,
            'pending_orders' => (clone $ordersQuery)->where('status', Order::STATUS_PENDING_PAYMENT)->count(),
        ];

        return view('reports.sales', compact('orders', 'summary', 'startDate', 'endDate', 'principalOptions', 'selectedPrincipalId'));
    }

    public function inventory(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        $request->validate([
            'principal_id' => ['nullable', 'exists:product_principals,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'insight' => ['nullable', 'in:out,slow,reorder,overstock,healthy'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $requestedPerPage = (int) $request->input('per_page', 25);
        $filters = [
            'principal_id' => $request->input('principal_id'),
            'search' => trim((string) $request->input('search', '')),
            'category' => $request->input('category'),
            'insight' => $request->input('insight'),
            'per_page' => in_array($requestedPerPage, [10, 25, 50, 100], true) ? $requestedPerPage : 25,
        ];
        $selectedPrincipalId = $filters['principal_id'];
        $principalOptions = ProductPrincipal::active()->orderBy('sort_order')->orderBy('name')->get();
        $categoryOptions = $this->inventoryCategoryOptions($filters);
        $insightOptions = $this->inventoryInsightOptions();
        $salesWindowStart = now()->subDays(30)->startOfDay();

        $productBaseQuery = $this->inventoryProductQuery($filters);
        $allSalesVelocity = $this->inventorySalesVelocity((clone $productBaseQuery)->pluck('id'), $salesWindowStart);

        if ($filters['insight']) {
            $matchingProductIds = $this->inventoryProductIdsForInsight((clone $productBaseQuery), $allSalesVelocity, $filters['insight']);
            $productBaseQuery->whereIn('id', $matchingProductIds);
        }

        $filteredProductIds = (clone $productBaseQuery)->pluck('id');

        $products = (clone $productBaseQuery)
            ->with('principal', 'unit', 'stock')
            ->orderBy('name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $productIds = $products->getCollection()->pluck('id');

        $salesVelocity = $this->inventorySalesVelocity($productIds, $salesWindowStart);

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

        $inventorySignals = (clone $productBaseQuery)->with('stock')->get()
            ->map(function (Product $product) use ($allSalesVelocity) {
                $quantity = $product->stock?->quantity ?? 0;
                $soldLast30Days = (int) ($allSalesVelocity[$product->id] ?? 0);
                $weeklySalesAverage = $soldLast30Days > 0 ? $soldLast30Days / (30 / 7) : 0;
                $weekCover = $weeklySalesAverage > 0 ? round($quantity / $weeklySalesAverage, 1) : null;

                return $this->inventorySignal($quantity, $soldLast30Days, $weekCover)['type'];
            });

        $summary = [
            'total_products' => (clone $productBaseQuery)->count(),
            'active_products' => (clone $productBaseQuery)->where('is_active', true)->count(),
            'stock_in' => StockMovement::whereBetween('created_at', [$startDate, $endDate])
                ->where('type', StockMovement::TYPE_IN)
                ->whereIn('product_id', $filteredProductIds)
                ->sum('quantity'),
            'stock_out' => StockMovement::whereBetween('created_at', [$startDate, $endDate])
                ->where('type', StockMovement::TYPE_OUT)
                ->whereIn('product_id', $filteredProductIds)
                ->sum('quantity'),
            'slow_moving' => $inventorySignals->filter(fn (string $type) => $type === 'slow')->count(),
            'overstock' => $inventorySignals->filter(fn (string $type) => $type === 'overstock')->count(),
        ];

        return view('reports.inventory', compact('products', 'summary', 'startDate', 'endDate', 'principalOptions', 'selectedPrincipalId', 'categoryOptions', 'insightOptions', 'filters'));
    }

    public function principal(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        $request->validate([
            'principal_id' => ['nullable', 'exists:product_principals,id'],
        ]);

        $selectedPrincipalId = $request->input('principal_id');

        $salesByPrincipal = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereNotNull('products.principal_id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereIn('orders.status', [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])
            ->selectRaw('products.principal_id, SUM(order_items.quantity) as sales_qty, SUM(order_items.subtotal) as sales_amount, COUNT(DISTINCT orders.id) as order_count')
            ->groupBy('products.principal_id')
            ->get()
            ->keyBy('principal_id');

        $purchaseByPrincipal = PurchaseOrderItem::query()
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->join('products', 'purchase_order_items.product_id', '=', 'products.id')
            ->whereNotNull('products.principal_id')
            ->whereBetween('purchase_orders.created_at', [$startDate, $endDate])
            ->where('purchase_orders.status', PurchaseOrder::STATUS_RECEIVED)
            ->selectRaw('products.principal_id, SUM(COALESCE(NULLIF(purchase_order_items.received_quantity, 0), purchase_order_items.quantity)) as purchase_qty, SUM(COALESCE(NULLIF(purchase_order_items.received_quantity, 0), purchase_order_items.quantity) * purchase_order_items.price) as purchase_amount, COUNT(DISTINCT purchase_orders.id) as po_count')
            ->groupBy('products.principal_id')
            ->get()
            ->keyBy('principal_id');

        $stockByPrincipal = ProductStock::query()
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->whereNotNull('products.principal_id')
            ->selectRaw('products.principal_id, SUM(product_stocks.quantity) as stock_qty, SUM(product_stocks.consignment_quantity) as consignment_qty')
            ->groupBy('products.principal_id')
            ->get()
            ->keyBy('principal_id');

        $productCounts = Product::query()
            ->whereNotNull('principal_id')
            ->selectRaw('principal_id, COUNT(*) as product_count, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_product_count')
            ->groupBy('principal_id')
            ->get()
            ->keyBy('principal_id');

        $principals = ProductPrincipal::query()
            ->withCount(['products', 'suppliers'])
            ->when($selectedPrincipalId, fn ($query) => $query->whereKey($selectedPrincipalId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (ProductPrincipal $principal) use ($salesByPrincipal, $purchaseByPrincipal, $stockByPrincipal, $productCounts) {
                $sales = $salesByPrincipal->get($principal->id);
                $purchase = $purchaseByPrincipal->get($principal->id);
                $stock = $stockByPrincipal->get($principal->id);
                $productCount = $productCounts->get($principal->id);
                $salesAmount = (int) ($sales->sales_amount ?? 0);
                $purchaseAmount = (int) ($purchase->purchase_amount ?? 0);

                $principal->sales_qty = (int) ($sales->sales_qty ?? 0);
                $principal->sales_amount = $salesAmount;
                $principal->order_count = (int) ($sales->order_count ?? 0);
                $principal->purchase_qty = (int) ($purchase->purchase_qty ?? 0);
                $principal->purchase_amount = $purchaseAmount;
                $principal->po_count = (int) ($purchase->po_count ?? 0);
                $principal->stock_qty = (int) ($stock->stock_qty ?? 0);
                $principal->consignment_qty = (int) ($stock->consignment_qty ?? 0);
                $principal->product_count = (int) ($productCount->product_count ?? $principal->products_count);
                $principal->active_product_count = (int) ($productCount->active_product_count ?? 0);
                $principal->gross_margin = $salesAmount - $purchaseAmount;
                $principal->gross_margin_percent = $salesAmount > 0 ? round(($principal->gross_margin / $salesAmount) * 100, 1) : null;

                return $principal;
            });

        $summary = [
            'principal_count' => ProductPrincipal::where('is_active', true)->count(),
            'product_count' => Product::whereNotNull('principal_id')->count(),
            'sales_amount' => $principals->sum('sales_amount'),
            'purchase_amount' => $principals->sum('purchase_amount'),
            'stock_qty' => $principals->sum('stock_qty'),
            'margin' => $principals->sum('gross_margin'),
        ];

        $principalOptions = ProductPrincipal::active()->orderBy('sort_order')->orderBy('name')->get();

        return view('reports.principal', compact(
            'principals',
            'principalOptions',
            'selectedPrincipalId',
            'summary',
            'startDate',
            'endDate'
        ));
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
        $selectedBranchId = $this->selectedReportBranchId($request);
        $canFilterBranches = !auth()->user()?->scopedCompanyBranchId();
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        ['profitLoss' => $profitLoss, 'balanceSheet' => $balanceSheet] = $this->financialStatements($startDate, $endDate, $selectedBranchId);

        return view('reports.financial', compact(
            'profitLoss',
            'balanceSheet',
            'startDate',
            'endDate',
            'companyBranches',
            'selectedBranchId',
            'canFilterBranches'
        ));
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

        if ($type === 'financial') {
            return $this->exportFinancial($request, $startDate, $endDate);
        }

        if ($type === 'ar-aging') {
            return $this->exportArAging($request);
        }

        if ($type === 'ap-aging') {
            return $this->exportApAging($request);
        }

        if ($type === 'sales') {
            return $this->exportSales($request, $startDate, $endDate);
        }

        if ($type === 'inventory') {
            return $this->exportInventory($request, $startDate, $endDate);
        }

        return response()->streamDownload(function () use ($type, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', ucfirst($type)]);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, ['Start Date', $startDate->toDateString()]);
            fputcsv($handle, ['End Date', $endDate->toDateString()]);
            fclose($handle);
        }, $type . '-report-' . now()->format('Ymd-His') . '.csv');
    }

    private function exportSales(Request $request, $startDate, $endDate): StreamedResponse
    {
        $request->validate([
            'principal_id' => ['nullable', 'exists:product_principals,id'],
        ]);

        $selectedPrincipalId = $request->input('principal_id');
        $principal = $selectedPrincipalId ? ProductPrincipal::find($selectedPrincipalId) : null;

        $orders = Order::with(['user', 'items.product.principal'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($selectedPrincipalId, function ($query) use ($selectedPrincipalId) {
                $query->whereHas('items.product', fn ($query) => $query->where('principal_id', $selectedPrincipalId));
            })
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($orders, $startDate, $endDate, $principal, $selectedPrincipalId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', 'Laporan Penjualan']);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, ['Start Date', $startDate->toDateString()]);
            fputcsv($handle, ['End Date', $endDate->toDateString()]);
            fputcsv($handle, ['Principal', $principal?->name ?? 'Semua Principal']);
            fputcsv($handle, []);
            fputcsv($handle, ['Order', 'Tanggal', 'Customer', 'Principal', 'Produk', 'Qty', 'Harga', 'Subtotal Item', 'Status']);

            foreach ($orders as $order) {
                $items = $selectedPrincipalId
                    ? $order->items->filter(fn ($item) => (int) $item->product?->principal_id === (int) $selectedPrincipalId)
                    : $order->items;

                foreach ($items as $item) {
                    fputcsv($handle, [
                        $order->order_number,
                        $order->created_at?->toDateString(),
                        $order->user?->name ?? '-',
                        $item->product?->principal?->name ?? '-',
                        $item->product_name ?: $item->product?->name ?? '-',
                        (int) $item->quantity,
                        (int) $item->price,
                        (int) $item->subtotal,
                        $order->status,
                    ]);
                }
            }

            fclose($handle);
        }, 'sales-report-' . now()->format('Ymd-His') . '.csv');
    }

    private function inventoryProductQuery(array $filters)
    {
        return Product::query()
            ->when($filters['principal_id'] ?? null, fn ($query, $principalId) => $query->where('principal_id', $principalId))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('category', 'like', '%' . $search . '%')
                        ->orWhereHas('principal', function ($query) use ($search) {
                            $query->where('name', 'like', '%' . $search . '%')
                                ->orWhere('code', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category));
    }

    private function inventoryCategoryOptions(array $filters)
    {
        return Product::query()
            ->when($filters['principal_id'] ?? null, fn ($query, $principalId) => $query->where('principal_id', $principalId))
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    private function inventoryInsightOptions(): array
    {
        return [
            'out' => 'Stok Habis',
            'slow' => 'Belum Bergerak 30 Hari',
            'reorder' => 'Perlu Reorder',
            'overstock' => 'Stok Berlebih',
            'healthy' => 'Sehat',
        ];
    }

    private function inventorySalesVelocity($productIds, $salesWindowStart)
    {
        if ($productIds->isEmpty()) {
            return collect();
        }

        return OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as sold_last_30_days'))
            ->whereIn('product_id', $productIds)
            ->whereHas('order', function ($query) use ($salesWindowStart) {
                $query->whereIn('status', [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])
                    ->where('created_at', '>=', $salesWindowStart);
            })
            ->groupBy('product_id')
            ->pluck('sold_last_30_days', 'product_id');
    }

    private function inventoryProductIdsForInsight($productQuery, $salesVelocity, string $selectedInsight)
    {
        return $productQuery->with('stock')->get()
            ->filter(function (Product $product) use ($salesVelocity, $selectedInsight) {
                $quantity = $product->stock?->quantity ?? 0;
                $soldLast30Days = (int) ($salesVelocity[$product->id] ?? 0);
                $weeklySalesAverage = $soldLast30Days > 0 ? $soldLast30Days / (30 / 7) : 0;
                $weekCover = $weeklySalesAverage > 0 ? round($quantity / $weeklySalesAverage, 1) : null;

                return $this->inventorySignal($quantity, $soldLast30Days, $weekCover)['type'] === $selectedInsight;
            })
            ->pluck('id')
            ->values();
    }

    private function exportInventory(Request $request, $startDate, $endDate): StreamedResponse
    {
        $request->validate([
            'principal_id' => ['nullable', 'exists:product_principals,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'insight' => ['nullable', 'in:out,slow,reorder,overstock,healthy'],
        ]);

        $filters = [
            'principal_id' => $request->input('principal_id'),
            'search' => trim((string) $request->input('search', '')),
            'category' => $request->input('category'),
            'insight' => $request->input('insight'),
        ];
        $principal = $filters['principal_id'] ? ProductPrincipal::find($filters['principal_id']) : null;
        $salesWindowStart = now()->subDays(30)->startOfDay();

        $productQuery = $this->inventoryProductQuery($filters);
        $allSalesVelocity = $this->inventorySalesVelocity((clone $productQuery)->pluck('id'), $salesWindowStart);

        if ($filters['insight']) {
            $matchingProductIds = $this->inventoryProductIdsForInsight((clone $productQuery), $allSalesVelocity, $filters['insight']);
            $productQuery->whereIn('id', $matchingProductIds);
        }

        $products = $productQuery
            ->with(['principal', 'unit', 'stock'])
            ->orderBy('name')
            ->get();

        $productIds = $products->pluck('id');
        $salesVelocity = $this->inventorySalesVelocity($productIds, $salesWindowStart);

        return response()->streamDownload(function () use ($products, $salesVelocity, $startDate, $endDate, $principal, $filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', 'Laporan Inventori']);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, ['Start Date', $startDate->toDateString()]);
            fputcsv($handle, ['End Date', $endDate->toDateString()]);
            fputcsv($handle, ['Principal', $principal?->name ?? 'Semua Principal']);
            fputcsv($handle, ['Search', $filters['search'] ?: '-']);
            fputcsv($handle, ['Category', $filters['category'] ?: 'Semua Kategori']);
            fputcsv($handle, ['Insight', $filters['insight'] ? ($this->inventoryInsightOptions()[$filters['insight']] ?? $filters['insight']) : 'Semua Insight']);
            fputcsv($handle, []);
            fputcsv($handle, ['Produk', 'Principal', 'Satuan', 'Stok', 'Min Stok', 'Max Stok', 'Terjual 30 Hari', 'Week Cover', 'Sinyal']);

            foreach ($products as $product) {
                $quantity = $product->stock?->quantity ?? 0;
                $soldLast30Days = (int) ($salesVelocity[$product->id] ?? 0);
                $weeklySalesAverage = $soldLast30Days > 0 ? $soldLast30Days / (30 / 7) : 0;
                $weekCover = $weeklySalesAverage > 0 ? round($quantity / $weeklySalesAverage, 1) : null;
                $signal = $this->inventorySignal($quantity, $soldLast30Days, $weekCover);

                fputcsv($handle, [
                    $product->name,
                    $product->principal?->name ?? '-',
                    $product->formatted_unit,
                    (int) $quantity,
                    (int) ($product->stock?->min_stock ?? 0),
                    (int) ($product->stock?->max_stock ?? 0),
                    $soldLast30Days,
                    $weekCover ?? '-',
                    $signal['label'],
                ]);
            }

            fclose($handle);
        }, 'inventory-report-' . now()->format('Ymd-His') . '.csv');
    }

    private function exportArAging(Request $request): StreamedResponse
    {
        $request->validate([
            'as_of_date' => ['nullable', 'date'],
            'company_branch_id' => ['nullable', 'exists:company_branches,id'],
        ]);

        $asOfDate = $request->filled('as_of_date')
            ? $request->date('as_of_date')->endOfDay()
            : now()->endOfDay();
        $branchScopeId = auth()->user()?->scopedCompanyBranchId();

        $invoices = ArInvoice::with(['order', 'customer', 'customerUser', 'companyBranch'])
            ->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', [ArInvoice::STATUS_PAID, ArInvoice::STATUS_VOID])
            ->when($branchScopeId, fn ($query) => $query->where('company_branch_id', $branchScopeId))
            ->when(!$branchScopeId && $request->filled('company_branch_id'), fn ($query) => $query->where('company_branch_id', $request->company_branch_id))
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();

        return response()->streamDownload(function () use ($invoices, $asOfDate) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', 'Umur Piutang']);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, ['As Of Date', $asOfDate->toDateString()]);
            fputcsv($handle, []);
            fputcsv($handle, ['No Invoice', 'Customer', 'Order', 'Cabang', 'Jatuh Tempo', 'Bucket', 'Hari Terlambat', 'Total Invoice', 'Terbayar', 'Credit Note', 'Outstanding']);

            foreach ($invoices as $invoice) {
                $bucket = $this->arAgingBucket($invoice, $asOfDate);
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $invoice->customer?->name ?? $invoice->customerUser?->name ?? '-',
                    $invoice->order?->order_number ?? '-',
                    $invoice->companyBranch?->name ?? '-',
                    $invoice->due_date?->toDateString() ?? '-',
                    $bucket['label'],
                    $bucket['days_overdue'],
                    (int) $invoice->total_amount,
                    (int) $invoice->paid_amount,
                    (int) $invoice->credit_note_amount,
                    (int) $invoice->outstanding_amount,
                ]);
            }

            fclose($handle);
        }, 'ar-aging-report-' . now()->format('Ymd-His') . '.csv');
    }

    private function exportApAging(Request $request): StreamedResponse
    {
        $request->validate([
            'as_of_date' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);

        $asOfDate = $request->filled('as_of_date')
            ? $request->date('as_of_date')->endOfDay()
            : now()->endOfDay();

        $invoices = ApInvoice::with(['purchaseOrder', 'supplier', 'companyBranch'])
            ->forUserBranch()
            ->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', [ApInvoice::STATUS_PAID, ApInvoice::STATUS_VOID])
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->supplier_id))
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();

        return response()->streamDownload(function () use ($invoices, $asOfDate) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', 'Umur Hutang']);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, ['As Of Date', $asOfDate->toDateString()]);
            fputcsv($handle, []);
            fputcsv($handle, ['No Invoice', 'Pemasok', 'PO', 'Cabang', 'Jatuh Tempo', 'Bucket', 'Hari Terlambat', 'Total Invoice', 'Terbayar', 'Debit Note', 'Outstanding']);

            foreach ($invoices as $invoice) {
                $bucket = $this->apAgingBucket($invoice, $asOfDate);
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $invoice->supplier?->name ?? '-',
                    $invoice->purchaseOrder?->po_number ?? '-',
                    $invoice->companyBranch?->name ?? '-',
                    $invoice->due_date?->toDateString() ?? '-',
                    $bucket['label'],
                    $bucket['days_overdue'],
                    (int) $invoice->total_amount,
                    (int) $invoice->paid_amount,
                    (int) $invoice->debit_note_amount,
                    (int) $invoice->outstanding_amount,
                ]);
            }

            fclose($handle);
        }, 'ap-aging-report-' . now()->format('Ymd-His') . '.csv');
    }

    private function exportFinancial(Request $request, $startDate, $endDate): StreamedResponse
    {
        $selectedBranchId = $this->selectedReportBranchId($request);
        ['profitLoss' => $profitLoss, 'balanceSheet' => $balanceSheet] = $this->financialStatements($startDate, $endDate, $selectedBranchId);

        return response()->streamDownload(function () use ($profitLoss, $balanceSheet, $startDate, $endDate, $selectedBranchId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', 'Laporan Keuangan']);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, ['Start Date', $startDate->toDateString()]);
            fputcsv($handle, ['End Date', $endDate->toDateString()]);
            fputcsv($handle, ['Branch Scope', $selectedBranchId ?: 'Semua Cabang']);
            fputcsv($handle, []);
            fputcsv($handle, ['Laba Rugi']);
            fputcsv($handle, ['Section', 'Code', 'Account', 'Amount']);
            $this->writeFinancialRows($handle, 'Pendapatan', $profitLoss['revenue']);
            fputcsv($handle, ['Total Pendapatan', '', '', $profitLoss['revenue_total']]);
            $this->writeFinancialRows($handle, 'Harga Pokok Penjualan', $profitLoss['cogs']);
            fputcsv($handle, ['Laba Kotor', '', '', $profitLoss['gross_profit']]);
            $this->writeFinancialRows($handle, 'Beban Operasional', $profitLoss['expenses']);
            fputcsv($handle, ['Laba Bersih', '', '', $profitLoss['net_income']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Neraca']);
            fputcsv($handle, ['Section', 'Code', 'Account', 'Amount']);
            $this->writeFinancialRows($handle, 'Aset', $balanceSheet['assets']);
            fputcsv($handle, ['Total Aset', '', '', $balanceSheet['total_assets']]);
            $this->writeFinancialRows($handle, 'Kewajiban', $balanceSheet['liabilities']);
            fputcsv($handle, ['Total Kewajiban', '', '', $balanceSheet['total_liabilities']]);
            $this->writeFinancialRows($handle, 'Ekuitas', $balanceSheet['equity']);
            fputcsv($handle, ['Total Kewajiban + Ekuitas', '', '', $balanceSheet['total_liabilities_equity']]);
            fputcsv($handle, ['Balance Status', '', '', $balanceSheet['is_balanced'] ? 'Balance' : 'Tidak Balance']);
            fclose($handle);
        }, 'financial-report-' . now()->format('Ymd-His') . '.csv');
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

    private function selectedReportBranchId(Request $request): mixed
    {
        return auth()->user()?->scopedCompanyBranchId()
            ?: ($request->filled('company_branch_id') ? $request->company_branch_id : null);
    }

    private function financialStatements($startDate, $endDate, mixed $selectedBranchId): array
    {
        $profitLossRows = $this->financialAccountRows(
            [ChartAccount::TYPE_REVENUE, ChartAccount::TYPE_COGS, ChartAccount::TYPE_EXPENSE],
            $selectedBranchId,
            fn ($journal) => $journal->whereBetween('journal_date', [$startDate->toDateString(), $endDate->toDateString()])
        );

        $balanceSheetRows = $this->financialAccountRows(
            [ChartAccount::TYPE_ASSET, ChartAccount::TYPE_LIABILITY, ChartAccount::TYPE_EQUITY],
            $selectedBranchId,
            fn ($journal) => $journal->where('journal_date', '<=', $endDate->toDateString())
        );

        $incomeToDateRows = $this->financialAccountRows(
            [ChartAccount::TYPE_REVENUE, ChartAccount::TYPE_COGS, ChartAccount::TYPE_EXPENSE],
            $selectedBranchId,
            fn ($journal) => $journal->where('journal_date', '<=', $endDate->toDateString())
        );

        $revenueTotal = $profitLossRows->where('account_type', ChartAccount::TYPE_REVENUE)->sum('amount');
        $cogsTotal = $profitLossRows->where('account_type', ChartAccount::TYPE_COGS)->sum('amount');
        $expenseTotal = $profitLossRows->where('account_type', ChartAccount::TYPE_EXPENSE)->sum('amount');
        $grossProfit = $revenueTotal - $cogsTotal;
        $netIncome = $grossProfit - $expenseTotal;
        $incomeToDate = $incomeToDateRows->where('account_type', ChartAccount::TYPE_REVENUE)->sum('amount')
            - $incomeToDateRows->where('account_type', ChartAccount::TYPE_COGS)->sum('amount')
            - $incomeToDateRows->where('account_type', ChartAccount::TYPE_EXPENSE)->sum('amount');

        $assetRows = $balanceSheetRows->where('account_type', ChartAccount::TYPE_ASSET)->values();
        $liabilityRows = $balanceSheetRows->where('account_type', ChartAccount::TYPE_LIABILITY)->values();
        $equityRows = $balanceSheetRows->where('account_type', ChartAccount::TYPE_EQUITY)->values();
        $equityRows->push([
            'code' => '-',
            'name' => 'Laba Berjalan',
            'account_type' => ChartAccount::TYPE_EQUITY,
            'amount' => $incomeToDate,
        ]);

        $balanceSheet = [
            'assets' => $assetRows,
            'liabilities' => $liabilityRows,
            'equity' => $equityRows,
            'total_assets' => $assetRows->sum('amount'),
            'total_liabilities' => $liabilityRows->sum('amount'),
            'total_equity' => $equityRows->sum('amount'),
        ];
        $balanceSheet['total_liabilities_equity'] = $balanceSheet['total_liabilities'] + $balanceSheet['total_equity'];
        $balanceSheet['is_balanced'] = $balanceSheet['total_assets'] === $balanceSheet['total_liabilities_equity'];

        return [
            'profitLoss' => [
                'revenue' => $profitLossRows->where('account_type', ChartAccount::TYPE_REVENUE)->values(),
                'cogs' => $profitLossRows->where('account_type', ChartAccount::TYPE_COGS)->values(),
                'expenses' => $profitLossRows->where('account_type', ChartAccount::TYPE_EXPENSE)->values(),
                'revenue_total' => $revenueTotal,
                'cogs_total' => $cogsTotal,
                'gross_profit' => $grossProfit,
                'expense_total' => $expenseTotal,
                'net_income' => $netIncome,
            ],
            'balanceSheet' => $balanceSheet,
        ];
    }

    private function writeFinancialRows($handle, string $section, $rows): void
    {
        foreach ($rows as $row) {
            fputcsv($handle, [$section, $row['code'], $row['name'], $row['amount']]);
        }
    }

    private function financialAccountRows(array $accountTypes, mixed $selectedBranchId, callable $journalDateScope)
    {
        return ChartAccount::query()
            ->where('is_active', true)
            ->whereIn('account_type', $accountTypes)
            ->when($branchScopeId = auth()->user()?->scopedCompanyBranchId(), function ($accountQuery) use ($branchScopeId) {
                $accountQuery->where(function ($scopeQuery) use ($branchScopeId) {
                    $scopeQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $branchScopeId);
                });
            })
            ->when(!auth()->user()?->scopedCompanyBranchId() && $selectedBranchId, function ($accountQuery) use ($selectedBranchId) {
                $selectedBranchId === 'global'
                    ? $accountQuery->whereNull('company_branch_id')
                    : $accountQuery->where(function ($scopeQuery) use ($selectedBranchId) {
                        $scopeQuery->whereNull('company_branch_id')
                            ->orWhere('company_branch_id', $selectedBranchId);
                    });
            })
            ->orderBy('code')
            ->get()
            ->map(function (ChartAccount $account) use ($selectedBranchId, $journalDateScope) {
                $debit = $this->financialLineSum($account, $selectedBranchId, $journalDateScope, 'debit_amount');
                $credit = $this->financialLineSum($account, $selectedBranchId, $journalDateScope, 'credit_amount');

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'account_type' => $account->account_type,
                    'amount' => $this->signedFinancialBalance($account, (int) $debit, (int) $credit),
                ];
            })
            ->filter(fn (array $row) => $row['amount'] !== 0)
            ->values();
    }

    private function financialLineSum(ChartAccount $account, mixed $selectedBranchId, callable $journalDateScope, string $column): int
    {
        return (int) JournalEntryLine::query()
            ->where('chart_account_id', $account->id)
            ->whereHas('journalEntry', function ($journal) use ($selectedBranchId, $journalDateScope) {
                $journal->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_VOID]);
                $journalDateScope($journal);

                if ($branchScopeId = auth()->user()?->scopedCompanyBranchId()) {
                    $journal->where(function ($scopeQuery) use ($branchScopeId) {
                        $scopeQuery->whereNull('company_branch_id')
                            ->orWhere('company_branch_id', $branchScopeId);
                    });
                } elseif ($selectedBranchId) {
                    $selectedBranchId === 'global'
                        ? $journal->whereNull('company_branch_id')
                        : $journal->where('company_branch_id', $selectedBranchId);
                }
            })
            ->sum($column);
    }

    private function signedFinancialBalance(ChartAccount $account, int $debit, int $credit): int
    {
        return $account->normal_balance === ChartAccount::BALANCE_DEBIT
            ? $debit - $credit
            : $credit - $debit;
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
