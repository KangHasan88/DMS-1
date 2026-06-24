<?php

namespace App\Http\Controllers;

use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeneralLedgerController extends Controller
{
    public function index(Request $request)
    {
        $accounts = $this->scopedAccounts()
            ->orderBy('code')
            ->get();

        $selectedAccount = $accounts->firstWhere('id', (int) $request->get('chart_account_id'))
            ?: $accounts->first();

        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $branchScopeId = $this->currentBranchScopeId();
        $selectedBranchId = $branchScopeId ?: ($request->filled('company_branch_id') ? $request->company_branch_id : null);
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$branchScopeId;

        $openingBalance = 0;
        $entries = collect();
        $runningBalance = 0;

        if ($selectedAccount) {
            $openingDebit = $this->lineQuery($selectedAccount, $selectedBranchId)
                ->whereHas('journalEntry', fn ($journal) => $journal->where('journal_date', '<', $dateFrom))
                ->sum('debit_amount');
            $openingCredit = $this->lineQuery($selectedAccount, $selectedBranchId)
                ->whereHas('journalEntry', fn ($journal) => $journal->where('journal_date', '<', $dateFrom))
                ->sum('credit_amount');
            $openingBalance = $this->signedBalance($selectedAccount, (int) $openingDebit, (int) $openingCredit);
            $runningBalance = $openingBalance;

            $entries = $this->lineQuery($selectedAccount, $selectedBranchId)
                ->with(['journalEntry.companyBranch', 'journalEntry.source', 'account'])
                ->whereHas('journalEntry', function ($journal) use ($dateFrom, $dateTo) {
                    $journal->whereBetween('journal_date', [$dateFrom, $dateTo]);
                })
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->orderBy('journal_entries.journal_date')
                ->orderBy('journal_entry_lines.id')
                ->select('journal_entry_lines.*')
                ->get()
                ->map(function (JournalEntryLine $line) use ($selectedAccount, &$runningBalance) {
                    $movement = $this->signedBalance($selectedAccount, (int) $line->debit_amount, (int) $line->credit_amount);
                    $runningBalance += $movement;
                    $line->running_balance = $runningBalance;

                    return $line;
                });
        }

        return view('general-ledger.index', compact(
            'accounts',
            'selectedAccount',
            'dateFrom',
            'dateTo',
            'selectedBranchId',
            'companyBranches',
            'canFilterBranches',
            'openingBalance',
            'entries',
            'runningBalance'
        ));
    }

    private function lineQuery(ChartAccount $account, mixed $selectedBranchId)
    {
        return JournalEntryLine::query()
            ->where('chart_account_id', $account->id)
            ->whereHas('journalEntry', function ($journal) use ($selectedBranchId) {
                $journal->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_VOID]);

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

    private function scopedAccounts()
    {
        return ChartAccount::query()
            ->where('is_active', true)
            ->when($branchScopeId = $this->currentBranchScopeId(), function ($accountQuery) use ($branchScopeId) {
                $accountQuery->where(function ($scopeQuery) use ($branchScopeId) {
                    $scopeQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $branchScopeId);
                });
            });
    }

    private function signedBalance(ChartAccount $account, int $debit, int $credit): int
    {
        return $account->normal_balance === ChartAccount::BALANCE_DEBIT
            ? $debit - $credit
            : $credit - $debit;
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }
}
