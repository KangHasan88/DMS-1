<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CompanyProfile;
use App\Models\DriverVehicleAssignment;
use App\Models\DeliveryVehicle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryVehicleController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;

        $vehicles = DeliveryVehicle::with('companyBranch', 'activeDriverAssignment.driver')
            ->when($branchScopeId, fn ($query) => $query->forCompanyBranch($branchScopeId))
            ->when($canFilterBranches && $request->filled('company_branch_id'), fn ($query) => $query->forCompanyBranch((int) $request->company_branch_id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->search($request->search)
            ->orderByRaw('company_branch_id is not null')
            ->orderBy('code')
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        $companyBranches = $this->availableCompanyBranches();
        $statuses = DeliveryVehicle::STATUS_LIST;

        return view('delivery-vehicles.index', compact('vehicles', 'companyBranches', 'canFilterBranches', 'statuses'));
    }

    public function create()
    {
        $companyBranches = $this->availableCompanyBranches();
        $branchLocked = (bool) $this->currentBranchScopeId();
        $drivers = $this->availableDrivers();

        return view('delivery-vehicles.create', compact('companyBranches', 'branchLocked', 'drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $validated['company_branch_id'] = $this->resolveCompanyBranchId($validated['company_branch_id'] ?? null);
        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = true;

        DB::transaction(function () use ($validated) {
            $driverId = $validated['primary_driver_id'] ?? null;
            unset($validated['primary_driver_id']);

            $vehicle = DeliveryVehicle::create($validated);
            $this->syncPrimaryDriver($vehicle, $driverId);
        });

        return redirect()->route('delivery-vehicles.index')
            ->with('success', 'Armada berhasil ditambahkan');
    }

    public function edit(DeliveryVehicle $deliveryVehicle)
    {
        $this->authorizeBranchVehicle($deliveryVehicle);

        $companyBranches = $this->availableCompanyBranches();
        $branchLocked = (bool) $this->currentBranchScopeId();
        $drivers = $this->availableDrivers();
        $deliveryVehicle->load('activeDriverAssignment.driver');

        return view('delivery-vehicles.edit', compact('deliveryVehicle', 'companyBranches', 'branchLocked', 'drivers'));
    }

    public function update(Request $request, DeliveryVehicle $deliveryVehicle)
    {
        $this->authorizeBranchVehicle($deliveryVehicle);

        $validated = $request->validate($this->rules($deliveryVehicle));

        $validated['company_branch_id'] = $this->resolveCompanyBranchId($validated['company_branch_id'] ?? $deliveryVehicle->company_branch_id);
        $validated['code'] = strtoupper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active');

        if (!$validated['is_active']) {
            $validated['status'] = DeliveryVehicle::STATUS_INACTIVE;
        }

        DB::transaction(function () use ($validated, $deliveryVehicle) {
            $driverId = $validated['primary_driver_id'] ?? null;
            unset($validated['primary_driver_id']);

            $deliveryVehicle->update($validated);
            $this->syncPrimaryDriver($deliveryVehicle, $driverId);
        });

        return redirect()->route('delivery-vehicles.index')
            ->with('success', 'Armada berhasil diperbarui');
    }

    private function rules(?DeliveryVehicle $vehicle = null): array
    {
        return [
            'company_branch_id' => 'nullable|exists:company_branches,id',
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:255',
            'vehicle_type' => 'required|in:' . implode(',', array_keys(DeliveryVehicle::TYPE_LIST)),
            'plate_number' => 'nullable|string|max:30',
            'capacity' => 'nullable|string|max:100',
            'status' => 'required|in:' . implode(',', array_keys(DeliveryVehicle::STATUS_LIST)),
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'primary_driver_id' => 'nullable|exists:users,id',
        ];
    }

    private function availableDrivers()
    {
        return User::with('companyBranch')
            ->role('kurir')
            ->active()
            ->when($this->currentBranchScopeId(), fn ($query, $branchId) => $query->where('company_branch_id', $branchId))
            ->orderBy('name')
            ->get();
    }

    private function syncPrimaryDriver(DeliveryVehicle $vehicle, ?int $driverId): void
    {
        $currentAssignment = DriverVehicleAssignment::active()
            ->where('delivery_vehicle_id', $vehicle->id)
            ->lockForUpdate()
            ->first();

        if (!$driverId) {
            if ($currentAssignment) {
                $currentAssignment->update(['ended_at' => now()]);
                ActivityLog::record('delivery_vehicles', 'driver_unassigned', 'Driver utama armada dilepas', $vehicle, [
                    'old_driver_id' => $currentAssignment->driver_id,
                ]);
            }
            return;
        }

        $driver = User::role('kurir')->active()->findOrFail($driverId);

        if ($vehicle->company_branch_id && $driver->company_branch_id && (int) $vehicle->company_branch_id !== (int) $driver->company_branch_id) {
            throw ValidationException::withMessages([
                'primary_driver_id' => 'Driver dan armada harus berada pada cabang yang sama.',
            ]);
        }

        if ($currentAssignment && (int) $currentAssignment->driver_id === (int) $driverId) {
            return;
        }

        DriverVehicleAssignment::active()
            ->where(function ($query) use ($vehicle, $driverId) {
                $query->where('delivery_vehicle_id', $vehicle->id)
                    ->orWhere('driver_id', $driverId);
            })
            ->lockForUpdate()
            ->update(['ended_at' => now()]);

        DriverVehicleAssignment::create([
            'driver_id' => $driverId,
            'delivery_vehicle_id' => $vehicle->id,
            'started_at' => now(),
            'assigned_by' => auth()->id(),
            'notes' => 'Armada utama driver',
        ]);

        ActivityLog::record('delivery_vehicles', 'driver_assigned', 'Driver utama armada diperbarui', $vehicle, [
            'old_driver_id' => $currentAssignment?->driver_id,
            'new_driver_id' => $driverId,
        ]);
    }

    private function currentBranchScopeId(): ?int
    {
        return auth()->user()?->scopedCompanyBranchId();
    }

    private function availableCompanyBranches()
    {
        $query = CompanyProfile::defaultProfile()->activeBranches();

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->whereKey($branchScopeId);
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }

    private function resolveCompanyBranchId(?int $requestedBranchId): ?int
    {
        if ($branchScopeId = $this->currentBranchScopeId()) {
            return $branchScopeId;
        }

        return $requestedBranchId;
    }

    private function authorizeBranchVehicle(DeliveryVehicle $vehicle): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && $vehicle->company_branch_id && (int) $vehicle->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }
}
