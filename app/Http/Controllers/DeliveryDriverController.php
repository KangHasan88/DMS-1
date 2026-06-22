<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Http\Request;

class DeliveryDriverController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = auth()->user()?->scopedCompanyBranchId();
        $canFilterBranches = !$branchScopeId;

        $drivers = User::with([
                'companyBranch',
                'activeDriverVehicleAssignment.vehicle',
            ])
            ->role('kurir')
            ->when($branchScopeId, fn ($query) => $query->where('company_branch_id', $branchScopeId))
            ->when(
                $canFilterBranches && $request->filled('company_branch_id'),
                fn ($query) => $query->where('company_branch_id', (int) $request->company_branch_id)
            )
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->status === 'active');
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        $companyBranches = CompanyProfile::defaultProfile()
            ->activeBranches()
            ->when($branchScopeId, fn ($query) => $query->whereKey($branchScopeId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('delivery-drivers.index', compact(
            'drivers',
            'companyBranches',
            'canFilterBranches'
        ));
    }
}
