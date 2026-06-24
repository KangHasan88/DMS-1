<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriodLock;
use App\Models\ActivityLog;
use App\Models\CompanyBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingPeriodLockController extends Controller
{
    public function index()
    {
        $locks = AccountingPeriodLock::with(['companyBranch', 'lockedBy', 'unlockedBy'])
            ->latest('date_to')
            ->latest('id')
            ->paginate(15);
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();

        return view('accounting-period-locks.index', compact('locks', 'companyBranches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'company_branch_id' => ['nullable', 'exists:company_branches,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $branchId = $validated['company_branch_id'] ?? null;
        $overlap = AccountingPeriodLock::query()
            ->locked()
            ->whereDate('date_from', '<=', $validated['date_to'])
            ->whereDate('date_to', '>=', $validated['date_from'])
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($scopeQuery) use ($branchId) {
                    $scopeQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $branchId);
                });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'date_from' => 'Periode yang dipilih overlap dengan periode yang masih terkunci.',
            ]);
        }

        $lock = DB::transaction(function () use ($validated, $branchId) {
            $lock = AccountingPeriodLock::create([
                'company_branch_id' => $branchId,
                'date_from' => $validated['date_from'],
                'date_to' => $validated['date_to'],
                'status' => AccountingPeriodLock::STATUS_LOCKED,
                'reason' => $validated['reason'],
                'locked_by' => Auth::id(),
                'locked_at' => now(),
            ]);

            ActivityLog::record('accounting_period_locks', 'locked', 'Periode akuntansi dikunci', $lock, [
                'date_from' => $lock->date_from?->toDateString(),
                'date_to' => $lock->date_to?->toDateString(),
                'company_branch_id' => $lock->company_branch_id,
                'reason' => $lock->reason,
            ]);

            return $lock;
        });

        return redirect()->route('accounting-period-locks.index')
            ->with('success', 'Periode akuntansi berhasil dikunci.');
    }

    public function unlock(Request $request, AccountingPeriodLock $accountingPeriodLock)
    {
        $validated = $request->validate([
            'unlock_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($accountingPeriodLock->status === AccountingPeriodLock::STATUS_UNLOCKED) {
            return back()->with('error', 'Periode ini sudah dibuka.');
        }

        $accountingPeriodLock->forceFill([
            'status' => AccountingPeriodLock::STATUS_UNLOCKED,
            'unlocked_by' => Auth::id(),
            'unlocked_at' => now(),
            'unlock_reason' => $validated['unlock_reason'],
        ])->save();

        ActivityLog::record('accounting_period_locks', 'unlocked', 'Periode akuntansi dibuka kembali', $accountingPeriodLock, [
            'date_from' => $accountingPeriodLock->date_from?->toDateString(),
            'date_to' => $accountingPeriodLock->date_to?->toDateString(),
            'unlock_reason' => $accountingPeriodLock->unlock_reason,
        ]);

        return redirect()->route('accounting-period-locks.index')
            ->with('success', 'Periode akuntansi berhasil dibuka kembali.');
    }
}
