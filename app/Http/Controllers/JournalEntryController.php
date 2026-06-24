<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\JournalEntry;
use App\Services\AccountingPeriodLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = JournalEntry::with(['companyBranch', 'postedBy'])
            ->latest('journal_date')
            ->latest('id');

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where(function ($journalQuery) use ($branchScopeId) {
                $journalQuery->whereNull('company_branch_id')
                    ->orWhere('company_branch_id', $branchScopeId);
            });
        } elseif ($request->filled('company_branch_id')) {
            $request->company_branch_id === 'global'
                ? $query->whereNull('company_branch_id')
                : $query->where('company_branch_id', $request->company_branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('journal_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $journals = $query->paginate($request->get('per_page', 10))->withQueryString();
        $accounts = $this->scopedAccounts()->orderBy('code')->get();
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$this->currentBranchScopeId();
        $statuses = JournalEntry::STATUS_LIST;

        return view('journal-entries.index', compact(
            'journals',
            'accounts',
            'companyBranches',
            'canFilterBranches',
            'statuses'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'company_branch_id' => ['nullable', 'exists:company_branches,id'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_account_id' => ['nullable', 'exists:chart_accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit_amount' => ['nullable', 'integer', 'min:0'],
            'lines.*.credit_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $branchId = $this->resolvedBranchId($validated['company_branch_id'] ?? null);
        $lines = $this->validatedLines($validated['lines'], $branchId);
        $debitTotal = array_sum(array_column($lines, 'debit_amount'));
        $creditTotal = array_sum(array_column($lines, 'credit_amount'));

        if ($debitTotal !== $creditTotal) {
            throw ValidationException::withMessages([
                'lines' => 'Total debit dan kredit harus sama.',
            ]);
        }

        $branch = $branchId ? CompanyBranch::find($branchId) : null;

        try {
            app(AccountingPeriodLockService::class)->assertOpen($validated['journal_date'], $branchId);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $journal = DB::transaction(function () use ($validated, $lines, $branchId, $branch, $debitTotal, $creditTotal) {
            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::nextJournalNumber($branch),
                'journal_date' => $validated['journal_date'],
                'description' => $validated['description'],
                'company_branch_id' => $branchId,
                'status' => JournalEntry::STATUS_POSTED,
                'debit_total' => $debitTotal,
                'credit_total' => $creditTotal,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $journal->lines()->create($line);
            }

            ActivityLog::record('journal_entries', 'posted', 'Jurnal umum diposting', $journal, [
                'journal_number' => $journal->journal_number,
                'debit_total' => $journal->debit_total,
                'credit_total' => $journal->credit_total,
            ]);

            return $journal;
        });

        return redirect()->route('journal-entries.show', $journal)
            ->with('success', 'Jurnal umum berhasil diposting.');
    }

    public function show(JournalEntry $journalEntry)
    {
        $this->authorizeBranch($journalEntry);

        $journalEntry->load(['lines.account', 'companyBranch', 'postedBy']);

        return view('journal-entries.show', compact('journalEntry'));
    }

    public function void(Request $request, JournalEntry $journalEntry)
    {
        $this->authorizeBranch($journalEntry);

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($journalEntry->status === JournalEntry::STATUS_VOID) {
            return back()->with('error', 'Jurnal ini sudah void.');
        }

        if ($journalEntry->source_type) {
            return back()->with('error', 'Jurnal otomatis harus dibatalkan dari dokumen sumber.');
        }

        $journalEntry->loadMissing(['lines', 'companyBranch']);

        try {
            app(AccountingPeriodLockService::class)->assertOpen($journalEntry->journal_date?->toDateString() ?? now()->toDateString(), $journalEntry->company_branch_id);
            app(AccountingPeriodLockService::class)->assertOpen(now()->toDateString(), $journalEntry->company_branch_id);
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $reversal = DB::transaction(function () use ($journalEntry, $validated) {
            $journalEntry->forceFill([
                'status' => JournalEntry::STATUS_VOID,
                'voided_by' => Auth::id(),
                'voided_at' => now(),
                'void_reason' => $validated['void_reason'],
            ])->save();

            $reversal = JournalEntry::create([
                'journal_number' => JournalEntry::nextJournalNumber($journalEntry->companyBranch),
                'journal_date' => now()->toDateString(),
                'description' => 'Reversal ' . $journalEntry->journal_number . ' - ' . $validated['void_reason'],
                'company_branch_id' => $journalEntry->company_branch_id,
                'status' => JournalEntry::STATUS_POSTED,
                'source_type' => JournalEntry::class,
                'source_id' => $journalEntry->id,
                'debit_total' => $journalEntry->credit_total,
                'credit_total' => $journalEntry->debit_total,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            foreach ($journalEntry->lines as $line) {
                $reversal->lines()->create([
                    'chart_account_id' => $line->chart_account_id,
                    'description' => 'Reversal: ' . ($line->description ?: $journalEntry->journal_number),
                    'debit_amount' => $line->credit_amount,
                    'credit_amount' => $line->debit_amount,
                ]);
            }

            ActivityLog::record('journal_entries', 'voided', 'Jurnal umum di-void dan reversal diposting', $journalEntry, [
                'journal_number' => $journalEntry->journal_number,
                'reversal_journal_number' => $reversal->journal_number,
                'void_reason' => $validated['void_reason'],
            ]);

            return $reversal;
        });

        return redirect()->route('journal-entries.show', $reversal)
            ->with('success', 'Jurnal berhasil di-void dan jurnal reversal berhasil diposting.');
    }

    private function validatedLines(array $inputLines, ?int $branchId): array
    {
        $allowedAccountIds = $this->scopedAccounts($branchId)->pluck('id')->all();
        $lines = [];

        foreach ($inputLines as $index => $line) {
            $accountId = (int) ($line['chart_account_id'] ?? 0);
            $debit = (int) ($line['debit_amount'] ?? 0);
            $credit = (int) ($line['credit_amount'] ?? 0);

            if (!$accountId && !$debit && !$credit) {
                continue;
            }

            if (!$accountId || !in_array($accountId, $allowedAccountIds, true)) {
                throw ValidationException::withMessages([
                    "lines.{$index}.chart_account_id" => 'Akun tidak tersedia untuk scope cabang ini.',
                ]);
            }

            if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                throw ValidationException::withMessages([
                    "lines.{$index}.debit_amount" => 'Satu baris jurnal harus berisi debit atau kredit saja.',
                ]);
            }

            $lines[] = [
                'chart_account_id' => $accountId,
                'description' => $line['description'] ?? null,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
            ];
        }

        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'Jurnal minimal memiliki dua baris.',
            ]);
        }

        return $lines;
    }

    private function scopedAccounts(?int $targetBranchId = null)
    {
        $branchId = $this->currentBranchScopeId() ?: $targetBranchId;

        return ChartAccount::query()
            ->where('is_active', true)
            ->when($branchId, function ($accountQuery) use ($branchId) {
                $accountQuery->where(function ($scopeQuery) use ($branchId) {
                    $scopeQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $branchId);
                });
            });
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function resolvedBranchId(?string $branchId): ?int
    {
        if ($scopeId = $this->currentBranchScopeId()) {
            return $scopeId;
        }

        return $branchId ? (int) $branchId : null;
    }

    private function authorizeBranch(JournalEntry $journal): void
    {
        if (($branchScopeId = $this->currentBranchScopeId())
            && $journal->company_branch_id
            && (int) $journal->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }
}
