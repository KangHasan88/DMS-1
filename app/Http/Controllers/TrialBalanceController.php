<?php

namespace App\Http\Controllers;

use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TrialBalanceController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $branchScopeId = $this->currentBranchScopeId();
        $selectedBranchId = $branchScopeId ?: ($request->filled('company_branch_id') ? $request->company_branch_id : null);
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$branchScopeId;

        $accounts = $this->scopedAccounts($selectedBranchId)
            ->orderBy('code')
            ->get();

        $rows = $accounts->map(function (ChartAccount $account) use ($dateFrom, $dateTo, $selectedBranchId) {
            $openingDebit = $this->lineQuery($account, $selectedBranchId)
                ->whereHas('journalEntry', fn ($journal) => $journal->where('journal_date', '<', $dateFrom))
                ->sum('debit_amount');
            $openingCredit = $this->lineQuery($account, $selectedBranchId)
                ->whereHas('journalEntry', fn ($journal) => $journal->where('journal_date', '<', $dateFrom))
                ->sum('credit_amount');
            $periodDebit = $this->lineQuery($account, $selectedBranchId)
                ->whereHas('journalEntry', fn ($journal) => $journal->whereBetween('journal_date', [$dateFrom, $dateTo]))
                ->sum('debit_amount');
            $periodCredit = $this->lineQuery($account, $selectedBranchId)
                ->whereHas('journalEntry', fn ($journal) => $journal->whereBetween('journal_date', [$dateFrom, $dateTo]))
                ->sum('credit_amount');

            $opening = $this->trialColumns($account, (int) $openingDebit, (int) $openingCredit);
            $ending = $this->trialColumns($account, (int) $openingDebit + (int) $periodDebit, (int) $openingCredit + (int) $periodCredit);

            return [
                'account' => $account,
                'opening_debit' => $opening['debit'],
                'opening_credit' => $opening['credit'],
                'period_debit' => (int) $periodDebit,
                'period_credit' => (int) $periodCredit,
                'ending_debit' => $ending['debit'],
                'ending_credit' => $ending['credit'],
            ];
        });

        $totals = $this->totals($rows);
        $isBalanced = $totals['period_debit'] === $totals['period_credit']
            && $totals['ending_debit'] === $totals['ending_credit'];

        return view('trial-balance.index', compact(
            'dateFrom',
            'dateTo',
            'selectedBranchId',
            'companyBranches',
            'canFilterBranches',
            'rows',
            'totals',
            'isBalanced'
        ));
    }

    private function lineQuery(ChartAccount $account, mixed $selectedBranchId)
    {
        return JournalEntryLine::query()
            ->where('chart_account_id', $account->id)
            ->whereHas('journalEntry', function ($journal) use ($selectedBranchId) {
                $journal->where('status', JournalEntry::STATUS_POSTED);

                if ($branchScopeId = $this->currentBranchScopeId()) {
                    $journal->where(function ($scopeQuery) use ($branchScopeId) {
                        $scopeQuery->whereNull('company_branch_id')
                            ->orWhere('company_branch_id', $branchScopeId);
                    });
                } elseif ($selectedBranchId) {
                    $selectedBranchId === 'global'
                        ? $journal->whereNull('company_branch_id')
                        : $journal->where('company_branch_id', $selectedBranchId);
                }
            });
    }

    private function scopedAccounts(mixed $selectedBranchId)
    {
        return ChartAccount::query()
            ->where('is_active', true)
            ->when($branchScopeId = $this->currentBranchScopeId(), function ($accountQuery) use ($branchScopeId) {
                $accountQuery->where(function ($scopeQuery) use ($branchScopeId) {
                    $scopeQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $branchScopeId);
                });
            })
            ->when(!$this->currentBranchScopeId() && $selectedBranchId, function ($accountQuery) use ($selectedBranchId) {
                $selectedBranchId === 'global'
                    ? $accountQuery->whereNull('company_branch_id')
                    : $accountQuery->where(function ($scopeQuery) use ($selectedBranchId) {
                        $scopeQuery->whereNull('company_branch_id')
                            ->orWhere('company_branch_id', $selectedBranchId);
                    });
            });
    }

    private function trialColumns(ChartAccount $account, int $debit, int $credit): array
    {
        $signed = $account->normal_balance === ChartAccount::BALANCE_DEBIT
            ? $debit - $credit
            : $credit - $debit;
        $normalColumn = $account->normal_balance === ChartAccount::BALANCE_DEBIT ? 'debit' : 'credit';
        $oppositeColumn = $normalColumn === 'debit' ? 'credit' : 'debit';

        return [
            'debit' => $signed >= 0 && $normalColumn === 'debit' ? $signed : ($signed < 0 && $oppositeColumn === 'debit' ? abs($signed) : 0),
            'credit' => $signed >= 0 && $normalColumn === 'credit' ? $signed : ($signed < 0 && $oppositeColumn === 'credit' ? abs($signed) : 0),
        ];
    }

    private function totals(Collection $rows): array
    {
        return [
            'opening_debit' => $rows->sum('opening_debit'),
            'opening_credit' => $rows->sum('opening_credit'),
            'period_debit' => $rows->sum('period_debit'),
            'period_credit' => $rows->sum('period_credit'),
            'ending_debit' => $rows->sum('ending_debit'),
            'ending_credit' => $rows->sum('ending_credit'),
        ];
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }
}
