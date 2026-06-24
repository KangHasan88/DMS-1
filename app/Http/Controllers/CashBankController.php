<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\AccountingPeriodLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashBankController extends Controller
{
    public function index(Request $request)
    {
        $cashAccounts = $this->scopedCashAccounts()
            ->orderBy('code')
            ->get();

        $selectedAccount = $cashAccounts->firstWhere('id', (int) $request->get('chart_account_id'))
            ?: $cashAccounts->first();

        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $branchScopeId = $this->currentBranchScopeId();
        $selectedBranchId = $branchScopeId ?: ($request->filled('company_branch_id') ? $request->company_branch_id : null);
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$branchScopeId;
        $expenseAccounts = $this->scopedExpenseAccounts()
            ->orderBy('code')
            ->get();

        $accountSummaries = $cashAccounts->map(function (ChartAccount $account) use ($dateFrom, $dateTo, $selectedBranchId) {
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

            $openingBalance = $this->signedBalance($account, (int) $openingDebit, (int) $openingCredit);
            $endingBalance = $openingBalance + $this->signedBalance($account, (int) $periodDebit, (int) $periodCredit);

            return [
                'account' => $account,
                'opening_balance' => $openingBalance,
                'period_debit' => (int) $periodDebit,
                'period_credit' => (int) $periodCredit,
                'ending_balance' => $endingBalance,
            ];
        });

        $openingBalance = 0;
        $entries = collect();
        $runningBalance = 0;

        if ($selectedAccount) {
            $selectedSummary = $accountSummaries->firstWhere('account.id', $selectedAccount->id);
            $openingBalance = $selectedSummary['opening_balance'] ?? 0;
            $runningBalance = $openingBalance;

            $entries = $this->lineQuery($selectedAccount, $selectedBranchId)
                ->with(['journalEntry.companyBranch', 'journalEntry.source', 'account'])
                ->whereHas('journalEntry', fn ($journal) => $journal->whereBetween('journal_date', [$dateFrom, $dateTo]))
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

        $totalOpeningBalance = $accountSummaries->sum('opening_balance');
        $totalDebit = $accountSummaries->sum('period_debit');
        $totalCredit = $accountSummaries->sum('period_credit');
        $totalEndingBalance = $accountSummaries->sum('ending_balance');

        return view('cash-bank.index', compact(
            'cashAccounts',
            'selectedAccount',
            'dateFrom',
            'dateTo',
            'selectedBranchId',
            'companyBranches',
            'canFilterBranches',
            'expenseAccounts',
            'accountSummaries',
            'openingBalance',
            'entries',
            'runningBalance',
            'totalOpeningBalance',
            'totalDebit',
            'totalCredit',
            'totalEndingBalance'
        ));
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'cash_account_id' => ['required', 'exists:chart_accounts,id'],
            'expense_account_id' => ['required', 'exists:chart_accounts,id'],
            'company_branch_id' => ['nullable', 'exists:company_branches,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        $branchId = $this->resolvedBranchId($validated['company_branch_id'] ?? null);
        $cashAccount = $this->findScopedCashAccount((int) $validated['cash_account_id'], $branchId);
        $expenseAccount = $this->findScopedExpenseAccount((int) $validated['expense_account_id'], $branchId);

        if (!$cashAccount || !$expenseAccount) {
            throw ValidationException::withMessages([
                'cash_account_id' => 'Akun kas/bank atau akun biaya tidak tersedia untuk scope cabang ini.',
            ]);
        }

        try {
            app(AccountingPeriodLockService::class)->assertOpen($validated['transaction_date'], $branchId);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $branch = $branchId ? CompanyBranch::find($branchId) : null;
        $amount = (int) $validated['amount'];
        $description = trim($validated['description']);
        $reference = trim((string) ($validated['reference_number'] ?? ''));

        $journal = DB::transaction(function () use ($validated, $branch, $branchId, $cashAccount, $expenseAccount, $amount, $description, $reference) {
            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::nextJournalNumber($branch),
                'journal_date' => $validated['transaction_date'],
                'description' => 'Biaya Operasional - ' . $description . ($reference ? ' (' . $reference . ')' : ''),
                'company_branch_id' => $branchId,
                'status' => JournalEntry::STATUS_POSTED,
                'debit_total' => $amount,
                'credit_total' => $amount,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            $journal->lines()->create([
                'chart_account_id' => $expenseAccount->id,
                'description' => $description . ($reference ? ' - Ref ' . $reference : ''),
                'debit_amount' => $amount,
                'credit_amount' => 0,
            ]);

            $journal->lines()->create([
                'chart_account_id' => $cashAccount->id,
                'description' => 'Pembayaran ' . $description . ($reference ? ' - Ref ' . $reference : ''),
                'debit_amount' => 0,
                'credit_amount' => $amount,
            ]);

            ActivityLog::record('cash_bank', 'expense_posted', 'Biaya operasional kas/bank dicatat', $journal, [
                'journal_number' => $journal->journal_number,
                'cash_account' => $cashAccount->code,
                'expense_account' => $expenseAccount->code,
                'amount' => $amount,
                'reference_number' => $reference ?: null,
            ]);

            return $journal;
        });

        return redirect()->route('cash-bank.index', [
            'chart_account_id' => $cashAccount->id,
            'date_from' => $validated['transaction_date'],
            'date_to' => $validated['transaction_date'],
            'company_branch_id' => $branchId,
        ])->with('success', 'Biaya operasional berhasil dicatat sebagai jurnal ' . $journal->journal_number . '.');
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

    private function scopedCashAccounts()
    {
        return ChartAccount::query()
            ->where('is_active', true)
            ->where('is_cash_account', true)
            ->when($branchScopeId = $this->currentBranchScopeId(), function ($accountQuery) use ($branchScopeId) {
                $accountQuery->where(function ($scopeQuery) use ($branchScopeId) {
                    $scopeQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $branchScopeId);
                });
            });
    }

    private function scopedExpenseAccounts()
    {
        return ChartAccount::query()
            ->where('is_active', true)
            ->where('account_type', ChartAccount::TYPE_EXPENSE)
            ->when($branchScopeId = $this->currentBranchScopeId(), function ($accountQuery) use ($branchScopeId) {
                $accountQuery->where(function ($scopeQuery) use ($branchScopeId) {
                    $scopeQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $branchScopeId);
                });
            });
    }

    private function findScopedCashAccount(int $accountId, ?int $branchId): ?ChartAccount
    {
        return $this->accountScopedToBranch(
            $this->scopedCashAccounts()->whereKey($accountId),
            $branchId
        )->first();
    }

    private function findScopedExpenseAccount(int $accountId, ?int $branchId): ?ChartAccount
    {
        return $this->accountScopedToBranch(
            $this->scopedExpenseAccounts()->whereKey($accountId),
            $branchId
        )->first();
    }

    private function accountScopedToBranch($query, ?int $branchId)
    {
        return $query->where(function ($scopeQuery) use ($branchId) {
            $scopeQuery->whereNull('company_branch_id')
                ->when($branchId, fn ($branchQuery) => $branchQuery->orWhere('company_branch_id', $branchId));
        });
    }

    private function resolvedBranchId(?int $requestedBranchId): ?int
    {
        if ($branchScopeId = $this->currentBranchScopeId()) {
            return $branchScopeId;
        }

        return $requestedBranchId;
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
