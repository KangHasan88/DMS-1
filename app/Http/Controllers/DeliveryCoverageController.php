<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\CustomerAddress;
use App\Models\DeliveryVehicle;
use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeliveryCoverageController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();

        $zones = DeliveryZone::query()
            ->with(['activeDepots', 'drivers.companyBranch', 'vehicles.companyBranch'])
            ->withCount('customerAddresses')
            ->when($branchScopeId, fn ($query) => $query->whereHas(
                'depots',
                fn ($depotQuery) => $depotQuery->where('company_branches.id', $branchScopeId)
            ))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(fn ($zoneQuery) => $zoneQuery
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return view('delivery-coverage.index', compact('zones', 'branchScopeId'));
    }

    public function create()
    {
        return view('delivery-coverage.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $zone = DB::transaction(function () use ($validated) {
            $zone = DeliveryZone::create([
                'company_profile_id' => CompanyProfile::defaultProfile()->id,
                'code' => strtoupper(trim($validated['code'])),
                'name' => trim($validated['name']),
                'description' => $validated['description'] ?? null,
                'is_active' => true,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            $this->syncCoverage($zone, $validated);
            ActivityLog::record('delivery_coverage', 'created', 'Zona pengiriman ditambahkan', $zone, [
                'code' => $zone->code,
                'depot_ids' => array_keys($this->depotSyncData($validated, $zone)),
            ]);

            return $zone;
        });

        return redirect()->route('delivery-coverage.edit', $zone)
            ->with('success', 'Zona pengiriman berhasil ditambahkan');
    }

    public function edit(DeliveryZone $deliveryCoverage)
    {
        $this->authorizeZone($deliveryCoverage);
        $deliveryCoverage->load('depots', 'drivers', 'vehicles', 'customerAddresses.customer.companyBranch');

        return view('delivery-coverage.edit', array_merge(
            $this->formData($deliveryCoverage),
            ['deliveryZone' => $deliveryCoverage]
        ));
    }

    public function update(Request $request, DeliveryZone $deliveryCoverage)
    {
        $this->authorizeZone($deliveryCoverage);
        $validated = $request->validate($this->rules($deliveryCoverage));

        DB::transaction(function () use ($validated, $deliveryCoverage) {
            $deliveryCoverage->update([
                'code' => strtoupper(trim($validated['code'])),
                'name' => trim($validated['name']),
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            $this->syncCoverage($deliveryCoverage, $validated);
            ActivityLog::record('delivery_coverage', 'updated', 'Zona pengiriman diperbarui', $deliveryCoverage, [
                'code' => $deliveryCoverage->code,
                'depot_ids' => array_keys($this->depotSyncData($validated, $deliveryCoverage)),
            ]);
        });

        return redirect()->route('delivery-coverage.edit', $deliveryCoverage)
            ->with('success', 'Delivery coverage berhasil diperbarui');
    }

    private function rules(?DeliveryZone $zone = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('delivery_zones', 'code')
                    ->where('company_profile_id', CompanyProfile::defaultProfile()->id)
                    ->ignore($zone?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'depot_ids' => ['required', 'array', 'min:1'],
            'depot_ids.*' => ['integer', 'exists:company_branches,id'],
            'depot_priority' => ['nullable', 'array'],
            'depot_priority.*' => ['nullable', 'integer', 'min:1', 'max:999'],
            'depot_capacity' => ['nullable', 'array'],
            'depot_capacity.*' => ['nullable', 'integer', 'min:1'],
            'customer_address_ids' => ['nullable', 'array'],
            'customer_address_ids.*' => ['integer', 'exists:customer_addresses,id'],
            'driver_ids' => ['nullable', 'array'],
            'driver_ids.*' => ['integer', 'exists:users,id'],
            'vehicle_ids' => ['nullable', 'array'],
            'vehicle_ids.*' => ['integer', 'exists:delivery_vehicles,id'],
        ];
    }

    private function syncCoverage(DeliveryZone $zone, array $validated): void
    {
        $this->ensureSubmittedRecordsAreAllowed($validated);

        $branchScopeId = $this->currentBranchScopeId();

        $zone->depots()->sync($this->depotSyncData($validated, $zone));
        $zone->drivers()->sync($this->preserveOutsideBranchIds(
            $zone->drivers,
            $validated['driver_ids'] ?? [],
            fn (User $driver) => $driver->company_branch_id
        ));
        $zone->vehicles()->sync($this->preserveOutsideBranchIds(
            $zone->vehicles,
            $validated['vehicle_ids'] ?? [],
            fn (DeliveryVehicle $vehicle) => $vehicle->company_branch_id
        ));

        CustomerAddress::where('delivery_zone_id', $zone->id)
            ->when($branchScopeId, fn ($query) => $query->whereHas(
                'customer',
                fn ($customerQuery) => $customerQuery->where('company_branch_id', $branchScopeId)
            ))
            ->whereNotIn('id', $validated['customer_address_ids'] ?? [])
            ->update([
                'delivery_zone_id' => null,
                'coverage_verified_at' => null,
                'coverage_verified_by' => null,
            ]);

        if (!empty($validated['customer_address_ids'])) {
            CustomerAddress::whereIn('id', $validated['customer_address_ids'])->update([
                'delivery_zone_id' => $zone->id,
                'coverage_verified_at' => now(),
                'coverage_verified_by' => auth()->id(),
            ]);
        }
    }

    private function depotSyncData(array $validated, ?DeliveryZone $zone = null): array
    {
        $depotIds = $validated['depot_ids'] ?? [];
        $sync = [];

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $depotIds = [$branchScopeId];

            if ($zone?->exists) {
                foreach ($zone->depots()->where('company_branches.id', '!=', $branchScopeId)->get() as $depot) {
                    $sync[$depot->id] = [
                        'priority' => (int) $depot->pivot->priority,
                        'max_daily_orders' => $depot->pivot->max_daily_orders,
                        'is_active' => (bool) $depot->pivot->is_active,
                    ];
                }
            }
        }

        foreach (array_values(array_unique(array_map('intval', $depotIds))) as $index => $depotId) {
            $sync[$depotId] = [
                'priority' => (int) ($validated['depot_priority'][$depotId] ?? ($index + 1)),
                'max_daily_orders' => $validated['depot_capacity'][$depotId] ?? null,
                'is_active' => true,
            ];
        }

        uasort($sync, fn ($left, $right) => $left['priority'] <=> $right['priority']);

        return $sync;
    }

    private function preserveOutsideBranchIds($currentRecords, array $submittedIds, callable $branchResolver): array
    {
        $branchScopeId = $this->currentBranchScopeId();
        if (!$branchScopeId) {
            return array_values(array_unique(array_map('intval', $submittedIds)));
        }

        $preservedIds = $currentRecords
            ->filter(fn ($record) => (int) $branchResolver($record) !== $branchScopeId)
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_map('intval', array_merge($preservedIds, $submittedIds))));
    }

    private function formData(?DeliveryZone $zone = null): array
    {
        $branchScopeId = $this->currentBranchScopeId();
        $currentDepotIds = $zone?->depots?->pluck('id')->filter()->unique()->values() ?? collect();
        $currentDriverIds = $zone?->drivers?->pluck('id')->filter()->unique()->values() ?? collect();
        $currentVehicleIds = $zone?->vehicles?->pluck('id')->filter()->unique()->values() ?? collect();

        $branches = CompanyBranch::query()
            ->where('company_profile_id', CompanyProfile::defaultProfile()->id)
            ->where(function ($query) use ($currentDepotIds) {
                $query->where('is_active', true);

                if ($currentDepotIds->isNotEmpty()) {
                    $query->orWhereIn('id', $currentDepotIds);
                }
            })
            ->when($branchScopeId, fn ($query) => $query->whereKey($branchScopeId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $addresses = CustomerAddress::query()
            ->with('customer.companyBranch', 'deliveryZone')
            ->where('is_active', true)
            ->whereIn('type', [CustomerAddress::TYPE_SHIPPING, CustomerAddress::TYPE_BOTH])
            ->when($branchScopeId, fn ($query) => $query->whereHas(
                'customer',
                fn ($customerQuery) => $customerQuery->where('company_branch_id', $branchScopeId)
            ))
            ->where(function ($query) use ($zone) {
                $query->whereNull('delivery_zone_id');
                if ($zone) {
                    $query->orWhere('delivery_zone_id', $zone->id);
                }
            })
            ->orderBy('customer_id')
            ->orderBy('label')
            ->get();

        $drivers = User::query()
            ->with('companyBranch')
            ->role('kurir')
            ->where(function ($query) use ($currentDriverIds) {
                $query->active();

                if ($currentDriverIds->isNotEmpty()) {
                    $query->orWhereIn('id', $currentDriverIds);
                }
            })
            ->when($branchScopeId, fn ($query) => $query->where('company_branch_id', $branchScopeId))
            ->orderBy('name')
            ->get();

        $vehicles = DeliveryVehicle::query()
            ->with('companyBranch')
            ->where(function ($query) use ($currentVehicleIds) {
                $query->active();

                if ($currentVehicleIds->isNotEmpty()) {
                    $query->orWhereIn('id', $currentVehicleIds);
                }
            })
            ->when($branchScopeId, fn ($query) => $query->forCompanyBranch($branchScopeId))
            ->orderBy('code')
            ->get();

        return compact('branches', 'addresses', 'drivers', 'vehicles', 'branchScopeId');
    }

    private function ensureSubmittedRecordsAreAllowed(array $validated): void
    {
        $branchScopeId = $this->currentBranchScopeId();
        if (!$branchScopeId) {
            return;
        }

        if (array_diff($validated['depot_ids'] ?? [], [$branchScopeId])) {
            abort(403);
        }

        $invalidAddress = CustomerAddress::whereIn('id', $validated['customer_address_ids'] ?? [])
            ->whereHas('customer', fn ($query) => $query->where('company_branch_id', '!=', $branchScopeId))
            ->exists();
        $invalidDriver = User::whereIn('id', $validated['driver_ids'] ?? [])
            ->where('company_branch_id', '!=', $branchScopeId)
            ->exists();
        $invalidVehicle = DeliveryVehicle::whereIn('id', $validated['vehicle_ids'] ?? [])
            ->whereNotNull('company_branch_id')
            ->where('company_branch_id', '!=', $branchScopeId)
            ->exists();

        abort_if($invalidAddress || $invalidDriver || $invalidVehicle, 403);
    }

    private function authorizeZone(DeliveryZone $zone): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && !$zone->depots()->whereKey($branchScopeId)->exists()) {
            abort(403);
        }
    }

    private function currentBranchScopeId(): ?int
    {
        return auth()->user()?->scopedCompanyBranchId();
    }
}
