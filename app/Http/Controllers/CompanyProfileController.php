<?php

namespace App\Http\Controllers;

use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyProfileController extends Controller
{
    public function index(Request $request)
    {
        $company = CompanyProfile::defaultProfile();

        if (!$company->branches()->exists()) {
            $company->branches()->create([
                'name' => data_get(config('invoice.branch', []), 'name', 'Cabang Utama'),
                'code' => $this->normalizeBranchCode(data_get(config('invoice.branch', []), 'code', 'MAIN')),
                'address' => data_get(config('invoice.branch', []), 'address'),
                'phone' => data_get(config('invoice.branch', []), 'phone'),
                'email' => data_get(config('invoice.company', []), 'email'),
                'is_invoice_default' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        $company->load('branches');
        $editingBranch = $request->filled('edit_branch')
            ? $company->branches()->whereKey($request->edit_branch)->first()
            : null;

        return view('company-profile.index', compact('company', 'editingBranch'));
    }

    public function updateCompany(Request $request)
    {
        $company = CompanyProfile::defaultProfile();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:3', 'regex:/^[A-Za-z0-9]+$/'],
            'display_name' => 'required|string|max:120',
            'legal_name' => 'required|string|max:160',
            'npwp' => 'nullable|string|max:60',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:120',
            'address' => 'nullable|string|max:500',
        ]);

        $validated['code'] = $this->normalizeCompanyCode($validated['code']);

        $company->update($validated);

        return redirect()->route('company-profile.index')
            ->with('success', 'Profil perusahaan berhasil diperbarui');
    }

    public function storeBranch(Request $request)
    {
        $company = CompanyProfile::defaultProfile();

        $validated = $request->validate($this->branchRules($company));
        $validated['company_profile_id'] = $company->id;
        $validated['code'] = $this->normalizeBranchCode($validated['code'] ?? null);
        $validated['is_active'] = $request->has('is_active');
        $validated['is_invoice_default'] = $request->has('is_invoice_default');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        DB::transaction(function () use ($company, $validated) {
            if ($validated['is_invoice_default']) {
                $company->branches()->update(['is_invoice_default' => false]);
            }

            $branch = CompanyBranch::create($validated);

            if (!$company->branches()->where('is_invoice_default', true)->exists()) {
                $branch->update(['is_invoice_default' => true, 'is_active' => true]);
            }
        });

        return redirect()->route('company-profile.index')
            ->with('success', 'Cabang berhasil ditambahkan');
    }

    public function updateBranch(Request $request, CompanyBranch $branch)
    {
        $company = CompanyProfile::defaultProfile();
        abort_unless($branch->company_profile_id === $company->id, 404);

        $validated = $request->validate($this->branchRules($company, $branch));
        $validated['code'] = $this->normalizeBranchCode($validated['code'] ?? null);
        $validated['is_active'] = $request->has('is_active');
        $validated['is_invoice_default'] = $request->has('is_invoice_default');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        DB::transaction(function () use ($company, $branch, $validated) {
            if ($validated['is_invoice_default']) {
                $company->branches()->whereKeyNot($branch->id)->update(['is_invoice_default' => false]);
                $validated['is_active'] = true;
            }

            $branch->update($validated);

            if (!$company->branches()->where('is_invoice_default', true)->exists()) {
                $fallbackBranch = $company->branches()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->first();

                if ($fallbackBranch) {
                    $fallbackBranch->update(['is_invoice_default' => true]);
                }
            }
        });

        return redirect()->route('company-profile.index')
            ->with('success', 'Cabang berhasil diperbarui');
    }

    public function toggleBranch(CompanyBranch $branch)
    {
        $company = CompanyProfile::defaultProfile();
        abort_unless($branch->company_profile_id === $company->id, 404);

        DB::transaction(function () use ($company, $branch) {
            $nextStatus = !$branch->is_active;
            $branch->update([
                'is_active' => $nextStatus,
                'is_invoice_default' => $nextStatus ? $branch->is_invoice_default : false,
            ]);

            if (!$company->branches()->where('is_invoice_default', true)->exists()) {
                $fallbackBranch = $company->branches()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->first();

                if ($fallbackBranch) {
                    $fallbackBranch->update(['is_invoice_default' => true]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => $branch->fresh()->is_active ? 'Cabang diaktifkan' : 'Cabang dinonaktifkan',
        ]);
    }

    public function setDefaultBranch(CompanyBranch $branch)
    {
        $company = CompanyProfile::defaultProfile();
        abort_unless($branch->company_profile_id === $company->id, 404);

        DB::transaction(function () use ($company, $branch) {
            $company->branches()->update(['is_invoice_default' => false]);
            $branch->update(['is_invoice_default' => true, 'is_active' => true]);
        });

        return redirect()->route('company-profile.index')
            ->with('success', 'Cabang default invoice berhasil diganti');
    }

    private function branchRules(CompanyProfile $company, ?CompanyBranch $branch = null): array
    {
        return [
            'name' => 'required|string|max:120',
            'code' => [
                'nullable',
                'string',
                'max:3',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('company_branches', 'code')
                    ->where('company_profile_id', $company->id)
                    ->ignore($branch ? $branch->id : null),
            ],
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:120',
            'address' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_invoice_default' => 'boolean',
        ];
    }

    private function normalizeBranchCode(?string $code): ?string
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        return CompanyProfile::normalizeCodePart($code, 'TNG');
    }

    private function normalizeCompanyCode(string $code): string
    {
        return CompanyProfile::normalizeCodePart($code, 'KMG');
    }
}
