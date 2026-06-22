<?php

namespace App\Http\Controllers;

use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChartAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = ChartAccount::with(['parent', 'companyBranch'])
            ->orderBy('code');

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where(function ($accountQuery) use ($branchScopeId) {
                $accountQuery->whereNull('company_branch_id')
                    ->orWhere('company_branch_id', $branchScopeId);
            });
        } elseif ($request->filled('company_branch_id')) {
            $request->company_branch_id === 'global'
                ? $query->whereNull('company_branch_id')
                : $query->where('company_branch_id', $request->company_branch_id);
        }

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->paginate($request->get('per_page', 15))->withQueryString();
        $accountTypes = ChartAccount::TYPE_LIST;
        $normalBalances = ChartAccount::BALANCE_LIST;
        $parentAccounts = ChartAccount::whereNull('parent_id')
            ->when($branchScopeId = $this->currentBranchScopeId(), function ($parentQuery) use ($branchScopeId) {
                $parentQuery->where(function ($scopeQuery) use ($branchScopeId) {
                    $scopeQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $branchScopeId);
                });
            })
            ->orderBy('code')
            ->get();
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$this->currentBranchScopeId();

        return view('chart-accounts.index', compact(
            'accounts',
            'accountTypes',
            'normalBalances',
            'parentAccounts',
            'companyBranches',
            'canFilterBranches'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedAccount($request);
        $validated['normal_balance'] = ($validated['normal_balance'] ?? null)
            ?: ChartAccount::defaultNormalBalance($validated['account_type']);
        $validated['company_branch_id'] = $this->resolvedBranchId($validated['company_branch_id'] ?? null);
        $validated['is_cash_account'] = $request->boolean('is_cash_account');
        $validated['is_active'] = true;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        ChartAccount::create($validated);

        return redirect()->route('chart-accounts.index')
            ->with('success', 'Daftar akun berhasil ditambahkan.');
    }

    public function update(Request $request, ChartAccount $chartAccount)
    {
        $this->authorizeBranch($chartAccount);

        $validated = $this->validatedAccount($request, $chartAccount);
        $validated['normal_balance'] = ($validated['normal_balance'] ?? null)
            ?: ChartAccount::defaultNormalBalance($validated['account_type']);
        $validated['company_branch_id'] = $this->resolvedBranchId($validated['company_branch_id'] ?? null);
        $validated['is_cash_account'] = $request->boolean('is_cash_account');
        $validated['updated_by'] = Auth::id();

        $chartAccount->update($validated);

        return redirect()->route('chart-accounts.index')
            ->with('success', 'Daftar akun berhasil diperbarui.');
    }

    public function toggle(ChartAccount $chartAccount)
    {
        $this->authorizeBranch($chartAccount);

        $chartAccount->update([
            'is_active' => ! $chartAccount->is_active,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('chart-accounts.index')
            ->with('success', 'Status akun berhasil diperbarui.');
    }

    private function validatedAccount(Request $request, ?ChartAccount $account = null): array
    {
        $branchScopeId = $this->currentBranchScopeId();
        $branchRule = $branchScopeId
            ? ['nullable', Rule::in([(string) $branchScopeId])]
            : ['nullable', 'exists:company_branches,id'];

        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('chart_accounts', 'code')->ignore($account?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'account_type' => ['required', Rule::in(array_keys(ChartAccount::TYPE_LIST))],
            'normal_balance' => ['nullable', Rule::in(array_keys(ChartAccount::BALANCE_LIST))],
            'parent_id' => ['nullable', 'exists:chart_accounts,id'],
            'company_branch_id' => $branchRule,
            'description' => ['nullable', 'string', 'max:500'],
        ]);
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

    private function authorizeBranch(ChartAccount $account): void
    {
        if (($branchScopeId = $this->currentBranchScopeId())
            && $account->company_branch_id
            && (int) $account->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }
}
