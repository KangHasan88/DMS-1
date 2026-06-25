<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\ArInvoice;
use App\Models\CompanyBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaxController extends Controller
{
    public function summary(Request $request)
    {
        $summary = $this->taxSummaryData($request);
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$this->currentBranchScopeId();

        return view('tax.summary', compact('summary', 'companyBranches', 'canFilterBranches'));
    }

    public function exportSummary(Request $request)
    {
        $summary = $this->taxSummaryData($request);

        return $this->csvResponse([
            ['metric', 'value'],
            ['period', $summary['period']],
            ['period_label', $summary['period_label']],
            ['output_documents', (string) $summary['output_count']],
            ['output_dpp', (string) $summary['output_dpp']],
            ['output_ppn', (string) $summary['output_ppn']],
            ['input_documents', (string) $summary['input_count']],
            ['creditable_input_dpp', (string) $summary['input_dpp']],
            ['creditable_input_ppn', (string) $summary['input_ppn']],
            ['net_ppn', (string) $summary['net_ppn']],
            ['net_position', $summary['net_label']],
            ['output_ready', (string) $summary['output_ready']],
            ['output_incomplete', (string) $summary['output_incomplete']],
            ['input_ready', (string) $summary['input_ready']],
            ['input_incomplete', (string) $summary['input_incomplete']],
        ], 'rekap-pajak-' . $summary['period'] . '-' . now()->format('His') . '.csv');
    }

    public function output(Request $request)
    {
        $query = $this->outputQuery($request);

        $invoices = $query->paginate($request->get('per_page', 10))->withQueryString();
        $statuses = ArInvoice::TAX_STATUS_LIST;
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$this->currentBranchScopeId();
        $summary = $this->outputSummary($query);

        return view('tax.output', compact('invoices', 'statuses', 'companyBranches', 'canFilterBranches', 'summary'));
    }

    public function exportOutput(Request $request)
    {
        $invoices = $this->outputQuery($request)->get();

        $rows = [[
            'invoice_number',
            'customer',
            'invoice_date',
            'tax_invoice_number',
            'tax_invoice_date',
            'tax_transaction_code',
            'tax_base_amount',
            'ppn_amount',
            'tax_status',
            'branch',
        ]];

        foreach ($invoices as $invoice) {
            $rows[] = [
                $invoice->invoice_number,
                $invoice->customer?->name ?? $invoice->customerUser?->name ?? '',
                $invoice->invoice_date?->format('Y-m-d') ?? '',
                $invoice->tax_invoice_number ?? '',
                $invoice->tax_invoice_date?->format('Y-m-d') ?? '',
                $invoice->tax_transaction_code ?? '',
                (string) (int) $invoice->tax_base_amount,
                (string) (int) $invoice->ppn_amount,
                $invoice->tax_status_label,
                $invoice->companyBranch?->name ?? '',
            ];
        }

        return $this->csvResponse($rows, 'pajak-keluaran-' . now()->format('Ymd-His') . '.csv');
    }

    public function outputImportTemplate()
    {
        return $this->csvResponse([
            ['invoice_number', 'tax_status', 'tax_invoice_number', 'tax_invoice_date', 'tax_error_message'],
            ['INV-DMS-MAI-20260625-0001', ArInvoice::TAX_APPROVED, '010.000-26.00000001', now()->toDateString(), ''],
            ['INV-DMS-MAI-20260625-0002', ArInvoice::TAX_REJECTED, '', '', 'NPWP customer tidak valid'],
        ], 'template-import-pajak-keluaran.csv');
    }

    public function markOutputExported(Request $request)
    {
        $candidates = $this->outputQuery($request)
            ->whereIn('tax_status', [ArInvoice::TAX_DRAFT, ArInvoice::TAX_READY]);
        $total = (clone $candidates)->count();
        $count = (clone $candidates)
            ->whereNotNull('tax_invoice_number')
            ->where('tax_invoice_number', '!=', '')
            ->whereNotNull('tax_invoice_date')
            ->whereIn('tax_status', [ArInvoice::TAX_DRAFT, ArInvoice::TAX_READY])
            ->update([
                'tax_status' => ArInvoice::TAX_EXPORTED,
                'tax_exported_at' => now(),
                'updated_at' => now(),
            ]);
        $skipped = $total - $count;

        return back()->with('success', $count . ' pajak keluaran ditandai exported. ' . $skipped . ' dilewati karena data faktur belum lengkap.');
    }

    public function markOutputApproved(Request $request)
    {
        $count = $this->outputQuery($request)
            ->where('tax_status', ArInvoice::TAX_EXPORTED)
            ->update([
                'tax_status' => ArInvoice::TAX_APPROVED,
                'tax_approved_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', $count . ' pajak keluaran ditandai approved.');
    }

    public function importOutputResults(Request $request)
    {
        $request->validate([
            'result_file' => ['required', 'file', 'max:2048'],
        ]);

        $result = $this->importCsvRows($request->file('result_file')->getRealPath(), function (array $row) {
            $invoiceNumber = trim((string) ($row['invoice_number'] ?? ''));
            $status = trim((string) ($row['tax_status'] ?? ''));

            if ($invoiceNumber === '' || !array_key_exists($status, ArInvoice::TAX_STATUS_LIST)) {
                return false;
            }

            $invoice = ArInvoice::where('invoice_number', $invoiceNumber)->first();

            if (!$invoice || !$this->canAccessBranch($invoice->company_branch_id)) {
                return false;
            }

            $payload = [
                'tax_status' => $status,
                'tax_invoice_number' => $row['tax_invoice_number'] ?? $invoice->tax_invoice_number,
                'tax_invoice_date' => $row['tax_invoice_date'] ?? $invoice->tax_invoice_date,
                'tax_error_message' => $row['tax_error_message'] ?? null,
            ];

            if ($status === ArInvoice::TAX_EXPORTED && !$invoice->tax_exported_at) {
                $payload['tax_exported_at'] = now();
            }

            if ($status === ArInvoice::TAX_APPROVED) {
                $payload['tax_approved_at'] = now();
                $payload['tax_error_message'] = null;
            }

            $invoice->update($payload);

            return true;
        });

        return back()->with('success', $result['updated'] . ' hasil pajak keluaran diimport. ' . $result['skipped'] . ' baris dilewati.');
    }

    public function updateOutput(Request $request, ArInvoice $arInvoice)
    {
        $this->ensureBranchAccess($arInvoice->company_branch_id);

        if ($arInvoice->status === ArInvoice::STATUS_VOID) {
            return back()->with('error', 'Invoice void tidak bisa diupdate pajaknya.');
        }

        $validated = $request->validate([
            'tax_status' => ['required', Rule::in(array_keys(ArInvoice::TAX_STATUS_LIST))],
            'tax_invoice_number' => ['nullable', 'string', 'max:80'],
            'tax_invoice_date' => ['nullable', 'date'],
            'tax_transaction_code' => ['nullable', 'string', 'max:10'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_error_message' => ['nullable', 'string', 'max:500'],
        ]);

        $validated = array_merge([
            'tax_invoice_number' => null,
            'tax_invoice_date' => null,
            'tax_transaction_code' => null,
            'tax_error_message' => null,
        ], $validated);

        if ($validated['tax_status'] === ArInvoice::TAX_EXPORTED && !$arInvoice->tax_exported_at) {
            $validated['tax_exported_at'] = now();
        }

        if ($validated['tax_status'] === ArInvoice::TAX_APPROVED && !$arInvoice->tax_approved_at) {
            $validated['tax_approved_at'] = now();
        }

        $arInvoice->update($validated);

        return back()->with('success', 'Data pajak keluaran berhasil diperbarui.');
    }

    public function input(Request $request)
    {
        $query = $this->inputQuery($request);

        $invoices = $query->paginate($request->get('per_page', 10))->withQueryString();
        $statuses = ApInvoice::TAX_STATUS_LIST;
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$this->currentBranchScopeId();
        $summary = $this->inputSummary($query);

        return view('tax.input', compact('invoices', 'statuses', 'companyBranches', 'canFilterBranches', 'summary'));
    }

    public function exportInput(Request $request)
    {
        $invoices = $this->inputQuery($request)->get();

        $rows = [[
            'invoice_number',
            'supplier',
            'invoice_date',
            'supplier_tax_invoice_number',
            'supplier_tax_invoice_date',
            'tax_base_amount',
            'ppn_amount',
            'tax_status',
            'branch',
        ]];

        foreach ($invoices as $invoice) {
            $rows[] = [
                $invoice->invoice_number,
                $invoice->supplier?->name ?? '',
                $invoice->invoice_date?->format('Y-m-d') ?? '',
                $invoice->supplier_tax_invoice_number ?? '',
                $invoice->supplier_tax_invoice_date?->format('Y-m-d') ?? '',
                (string) (int) $invoice->tax_base_amount,
                (string) (int) $invoice->ppn_amount,
                $invoice->tax_status_label,
                $invoice->companyBranch?->name ?? '',
            ];
        }

        return $this->csvResponse($rows, 'pajak-masukan-' . now()->format('Ymd-His') . '.csv');
    }

    public function inputImportTemplate()
    {
        return $this->csvResponse([
            ['invoice_number', 'tax_status', 'supplier_tax_invoice_number', 'supplier_tax_invoice_date', 'tax_error_message'],
            ['AP-DMS-MAI-20260625-0001', ApInvoice::TAX_APPROVED, '010.000-26.00000001', now()->toDateString(), ''],
            ['AP-DMS-MAI-20260625-0002', ApInvoice::TAX_REJECTED, '', '', 'NPWP supplier tidak valid'],
        ], 'template-import-pajak-masukan.csv');
    }

    public function markInputExported(Request $request)
    {
        $candidates = $this->inputQuery($request)
            ->whereIn('tax_status', [ApInvoice::TAX_DRAFT, ApInvoice::TAX_CLAIMABLE]);
        $total = (clone $candidates)->count();
        $count = (clone $candidates)
            ->whereNotNull('supplier_tax_invoice_number')
            ->where('supplier_tax_invoice_number', '!=', '')
            ->whereNotNull('supplier_tax_invoice_date')
            ->whereIn('tax_status', [ApInvoice::TAX_DRAFT, ApInvoice::TAX_CLAIMABLE])
            ->update([
                'tax_status' => ApInvoice::TAX_EXPORTED,
                'tax_exported_at' => now(),
                'updated_at' => now(),
            ]);
        $skipped = $total - $count;

        return back()->with('success', $count . ' pajak masukan ditandai exported. ' . $skipped . ' dilewati karena data faktur belum lengkap.');
    }

    public function markInputApproved(Request $request)
    {
        $count = $this->inputQuery($request)
            ->where('tax_status', ApInvoice::TAX_EXPORTED)
            ->update([
                'tax_status' => ApInvoice::TAX_APPROVED,
                'tax_approved_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', $count . ' pajak masukan ditandai approved.');
    }

    public function importInputResults(Request $request)
    {
        $request->validate([
            'result_file' => ['required', 'file', 'max:2048'],
        ]);

        $result = $this->importCsvRows($request->file('result_file')->getRealPath(), function (array $row) {
            $invoiceNumber = trim((string) ($row['invoice_number'] ?? ''));
            $status = trim((string) ($row['tax_status'] ?? ''));

            if ($invoiceNumber === '' || !array_key_exists($status, ApInvoice::TAX_STATUS_LIST)) {
                return false;
            }

            $invoice = ApInvoice::where('invoice_number', $invoiceNumber)->first();

            if (!$invoice || !$this->canAccessBranch($invoice->company_branch_id)) {
                return false;
            }

            $payload = [
                'tax_status' => $status,
                'supplier_tax_invoice_number' => $row['supplier_tax_invoice_number'] ?? $invoice->supplier_tax_invoice_number,
                'supplier_tax_invoice_date' => $row['supplier_tax_invoice_date'] ?? $invoice->supplier_tax_invoice_date,
                'tax_error_message' => $row['tax_error_message'] ?? null,
            ];

            if ($status === ApInvoice::TAX_EXPORTED && !$invoice->tax_exported_at) {
                $payload['tax_exported_at'] = now();
            }

            if ($status === ApInvoice::TAX_APPROVED) {
                $payload['tax_approved_at'] = now();
                $payload['tax_error_message'] = null;
            }

            $invoice->update($payload);

            return true;
        });

        return back()->with('success', $result['updated'] . ' hasil pajak masukan diimport. ' . $result['skipped'] . ' baris dilewati.');
    }

    public function updateInput(Request $request, ApInvoice $apInvoice)
    {
        $this->ensureBranchAccess($apInvoice->company_branch_id);

        if ($apInvoice->status === ApInvoice::STATUS_VOID) {
            return back()->with('error', 'Invoice void tidak bisa diupdate pajaknya.');
        }

        $validated = $request->validate([
            'tax_status' => ['required', Rule::in(array_keys(ApInvoice::TAX_STATUS_LIST))],
            'supplier_tax_invoice_number' => ['nullable', 'string', 'max:80'],
            'supplier_tax_invoice_date' => ['nullable', 'date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_error_message' => ['nullable', 'string', 'max:500'],
        ]);

        $validated = array_merge([
            'supplier_tax_invoice_number' => null,
            'supplier_tax_invoice_date' => null,
            'tax_error_message' => null,
        ], $validated);

        if ($validated['tax_status'] === ApInvoice::TAX_EXPORTED && !$apInvoice->tax_exported_at) {
            $validated['tax_exported_at'] = now();
        }

        if ($validated['tax_status'] === ApInvoice::TAX_APPROVED && !$apInvoice->tax_approved_at) {
            $validated['tax_approved_at'] = now();
        }

        $apInvoice->update($validated);

        return back()->with('success', 'Data pajak masukan berhasil diperbarui.');
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function outputQuery(Request $request, bool $applyStatusFilter = true)
    {
        $query = ArInvoice::with(['customer', 'customerUser', 'companyBranch'])
            ->where('status', '!=', ArInvoice::STATUS_VOID)
            ->where(function ($taxQuery) {
                $taxQuery->where('ppn_amount', '>', 0)
                    ->orWhere('tax_status', '!=', ArInvoice::TAX_NOT_REQUIRED);
            })
            ->latest('invoice_date')
            ->latest('id');

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where('company_branch_id', $branchScopeId);
        } elseif ($request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }

        if ($applyStatusFilter && $request->filled('tax_status')) {
            $query->where('tax_status', $request->tax_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('tax_invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customerUser', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    private function inputQuery(Request $request, bool $applyStatusFilter = true)
    {
        $query = ApInvoice::with(['supplier', 'companyBranch'])
            ->where('status', '!=', ApInvoice::STATUS_VOID)
            ->where(function ($taxQuery) {
                $taxQuery->where('ppn_amount', '>', 0)
                    ->orWhere('tax_status', '!=', ApInvoice::TAX_NOT_RECEIVED);
            })
            ->latest('invoice_date')
            ->latest('id');

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where('company_branch_id', $branchScopeId);
        } elseif ($request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }

        if ($applyStatusFilter && $request->filled('tax_status')) {
            $query->where('tax_status', $request->tax_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('supplier_tax_invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    private function taxPeriod(Request $request): array
    {
        $period = $request->input('period', now()->format('Y-m'));

        try {
            $periodStart = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        } catch (\Throwable) {
            $periodStart = now()->startOfMonth();
            $period = $periodStart->format('Y-m');
        }

        return [$period, $periodStart, (clone $periodStart)->endOfMonth()];
    }

    private function taxSummaryData(Request $request): array
    {
        [$period, $periodStart, $periodEnd] = $this->taxPeriod($request);

        $outputQuery = $this->applyOutputPeriod($this->outputQuery($request, false), $periodStart, $periodEnd);
        $inputQuery = $this->applyInputPeriod($this->inputQuery($request, false), $periodStart, $periodEnd);
        $creditableInputStatuses = [ApInvoice::TAX_CLAIMABLE, ApInvoice::TAX_EXPORTED, ApInvoice::TAX_APPROVED];
        $creditableInputQuery = (clone $inputQuery)->whereIn('tax_status', $creditableInputStatuses);

        $outputPpn = (int) (clone $outputQuery)->sum('ppn_amount');
        $inputPpn = (int) (clone $creditableInputQuery)->sum('ppn_amount');
        $netPpn = $outputPpn - $inputPpn;

        return [
            'period' => $period,
            'period_label' => $periodStart->translatedFormat('F Y'),
            'output_count' => (clone $outputQuery)->count(),
            'input_count' => (clone $inputQuery)->count(),
            'output_dpp' => (int) (clone $outputQuery)->sum('tax_base_amount'),
            'input_dpp' => (int) (clone $creditableInputQuery)->sum('tax_base_amount'),
            'output_ppn' => $outputPpn,
            'input_ppn' => $inputPpn,
            'net_ppn' => $netPpn,
            'net_label' => $netPpn > 0 ? 'Kurang Bayar' : ($netPpn < 0 ? 'Lebih Bayar' : 'Nihil'),
            'output_ready' => (clone $outputQuery)
                ->whereIn('tax_status', [ArInvoice::TAX_DRAFT, ArInvoice::TAX_READY])
                ->whereNotNull('tax_invoice_number')
                ->where('tax_invoice_number', '!=', '')
                ->whereNotNull('tax_invoice_date')
                ->count(),
            'output_incomplete' => (clone $outputQuery)
                ->whereIn('tax_status', [ArInvoice::TAX_DRAFT, ArInvoice::TAX_READY])
                ->where(function ($missing) {
                    $missing->whereNull('tax_invoice_number')
                        ->orWhere('tax_invoice_number', '')
                        ->orWhereNull('tax_invoice_date');
                })
                ->count(),
            'input_ready' => (clone $inputQuery)
                ->whereIn('tax_status', [ApInvoice::TAX_DRAFT, ApInvoice::TAX_CLAIMABLE])
                ->whereNotNull('supplier_tax_invoice_number')
                ->where('supplier_tax_invoice_number', '!=', '')
                ->whereNotNull('supplier_tax_invoice_date')
                ->count(),
            'input_incomplete' => (clone $inputQuery)
                ->whereIn('tax_status', [ApInvoice::TAX_DRAFT, ApInvoice::TAX_CLAIMABLE])
                ->where(function ($missing) {
                    $missing->whereNull('supplier_tax_invoice_number')
                        ->orWhere('supplier_tax_invoice_number', '')
                        ->orWhereNull('supplier_tax_invoice_date');
                })
                ->count(),
        ];
    }

    private function applyOutputPeriod($query, Carbon $periodStart, Carbon $periodEnd)
    {
        return $query->where(function ($dateQuery) use ($periodStart, $periodEnd) {
            $dateQuery->whereBetween('tax_invoice_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->orWhere(function ($fallback) use ($periodStart, $periodEnd) {
                    $fallback->whereNull('tax_invoice_date')
                        ->whereBetween('invoice_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
                });
        });
    }

    private function applyInputPeriod($query, Carbon $periodStart, Carbon $periodEnd)
    {
        return $query->where(function ($dateQuery) use ($periodStart, $periodEnd) {
            $dateQuery->whereBetween('supplier_tax_invoice_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->orWhere(function ($fallback) use ($periodStart, $periodEnd) {
                    $fallback->whereNull('supplier_tax_invoice_date')
                        ->whereBetween('invoice_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
                });
        });
    }

    private function outputSummary($query): array
    {
        $pendingStatuses = [ArInvoice::TAX_DRAFT, ArInvoice::TAX_READY];

        return [
            'tax_base_amount' => (clone $query)->sum('tax_base_amount'),
            'ppn_amount' => (clone $query)->sum('ppn_amount'),
            'count' => (clone $query)->count(),
            'ready_count' => (clone $query)
                ->whereIn('tax_status', $pendingStatuses)
                ->whereNotNull('tax_invoice_number')
                ->where('tax_invoice_number', '!=', '')
                ->whereNotNull('tax_invoice_date')
                ->count(),
            'incomplete_count' => (clone $query)
                ->whereIn('tax_status', $pendingStatuses)
                ->where(function ($missing) {
                    $missing->whereNull('tax_invoice_number')
                        ->orWhere('tax_invoice_number', '')
                        ->orWhereNull('tax_invoice_date');
                })
                ->count(),
            'exported_count' => (clone $query)->where('tax_status', ArInvoice::TAX_EXPORTED)->count(),
            'approved_count' => (clone $query)->where('tax_status', ArInvoice::TAX_APPROVED)->count(),
            'rejected_count' => (clone $query)->where('tax_status', ArInvoice::TAX_REJECTED)->count(),
        ];
    }

    private function inputSummary($query): array
    {
        $pendingStatuses = [ApInvoice::TAX_DRAFT, ApInvoice::TAX_CLAIMABLE];

        return [
            'tax_base_amount' => (clone $query)->sum('tax_base_amount'),
            'ppn_amount' => (clone $query)->sum('ppn_amount'),
            'count' => (clone $query)->count(),
            'ready_count' => (clone $query)
                ->whereIn('tax_status', $pendingStatuses)
                ->whereNotNull('supplier_tax_invoice_number')
                ->where('supplier_tax_invoice_number', '!=', '')
                ->whereNotNull('supplier_tax_invoice_date')
                ->count(),
            'incomplete_count' => (clone $query)
                ->whereIn('tax_status', $pendingStatuses)
                ->where(function ($missing) {
                    $missing->whereNull('supplier_tax_invoice_number')
                        ->orWhere('supplier_tax_invoice_number', '')
                        ->orWhereNull('supplier_tax_invoice_date');
                })
                ->count(),
            'exported_count' => (clone $query)->where('tax_status', ApInvoice::TAX_EXPORTED)->count(),
            'approved_count' => (clone $query)->where('tax_status', ApInvoice::TAX_APPROVED)->count(),
            'rejected_count' => (clone $query)->where('tax_status', ApInvoice::TAX_REJECTED)->count(),
        ];
    }

    private function csvResponse(array $rows, string $filename)
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function ensureBranchAccess(?int $companyBranchId): void
    {
        abort_unless($this->canAccessBranch($companyBranchId), 404);
    }

    private function canAccessBranch(?int $companyBranchId): bool
    {
        $branchScopeId = $this->currentBranchScopeId();

        return !$branchScopeId || (int) $companyBranchId === (int) $branchScopeId;
    }

    private function importCsvRows(string $path, callable $handler): array
    {
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($header) => str($header)->trim()->snake()->toString(), $headers);
        $updated = 0;
        $skipped = 0;

        while (($values = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, array_pad($values, count($headers), null));

            if (!$row || !$handler($row)) {
                $skipped++;
                continue;
            }

            $updated++;
        }

        fclose($handle);

        return compact('updated', 'skipped');
    }
}
