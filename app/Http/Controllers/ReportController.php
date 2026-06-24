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

        return response()->streamDownload(function () use ($type, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report', ucfirst($type)]);
            fputcsv($handle, ['Generated At', now()->toDateTimeString()]);
            fputcsv($handle, ['Start Date', $startDate->toDateString()]);
            fputcsv($handle, ['End Date', $endDate->toDateString()]);
            fclose($handle);
        }, $type . '-report-' . now()->format('Ymd-His') . '.csv');
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
