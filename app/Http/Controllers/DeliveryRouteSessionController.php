<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\DeliveryRouteSession;
use App\Models\DeliveryVehicle;
use App\Models\SalesTerritory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DeliveryRouteSessionController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;

        $sessions = DeliveryRouteSession::query()
            ->with([
                'companyBranch',
                'salesTerritory',
                'salesperson.companyBranch',
                'driver.companyBranch',
                'vehicle.companyBranch',
            ])
            ->withCount('orders')
            ->when($branchScopeId, fn ($query) => $query->where('company_branch_id', $branchScopeId))
            ->when($canFilterBranches && $request->filled('company_branch_id'), fn ($query) => $query->where('company_branch_id', (int) $request->company_branch_id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('selling_mode'), fn ($query) => $query->where('selling_mode', $request->selling_mode))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($sessionQuery) use ($search) {
                    $sessionQuery->where('route_code', 'like', "%{$search}%")
                        ->orWhereHas('salesTerritory', fn ($territoryQuery) => $territoryQuery->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('salesperson', fn ($personQuery) => $personQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('driver', fn ($driverQuery) => $driverQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($vehicleQuery) => $vehicleQuery->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('plate_number', 'like', "%{$search}%"))
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('route_date')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        $companyBranches = $this->availableCompanyBranches();
        $statuses = DeliveryRouteSession::STATUS_LIST;
        $modes = DeliveryRouteSession::MODE_LIST;

        return view('delivery-route-sessions.index', compact(
            'sessions',
            'companyBranches',
            'canFilterBranches',
            'statuses',
            'modes'
        ));
    }

    public function create()
    {
        return view('delivery-route-sessions.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $session = DB::transaction(function () use ($validated) {
            $branch = $this->resolveBranch($validated['company_branch_id']);
            $routeDate = Carbon::parse($validated['route_date'])->toDateString();
            $routeCode = $this->generateRouteCode($branch, $routeDate);
            $this->ensureUniqueRouteCode($routeCode);

            $session = DeliveryRouteSession::create([
                'company_profile_id' => CompanyProfile::defaultProfile()->id,
                'company_branch_id' => $branch->id,
                'sales_territory_id' => $validated['sales_territory_id'],
                'salesperson_id' => $validated['salesperson_id'],
                'driver_id' => $validated['driver_id'],
                'delivery_vehicle_id' => $validated['delivery_vehicle_id'],
                'route_code' => $routeCode,
                'route_date' => $routeDate,
                'selling_mode' => $validated['selling_mode'],
                'status' => $validated['status'],
                'opening_qty' => $validated['opening_qty'] ?? 0,
                'sold_qty' => $validated['sold_qty'] ?? 0,
                'returned_qty' => $validated['returned_qty'] ?? 0,
                'damaged_qty' => $validated['damaged_qty'] ?? 0,
                'started_at' => $this->shouldMarkStartedAt($validated['status']) ? now() : null,
                'closed_at' => $validated['status'] === DeliveryRouteSession::STATUS_CLOSED ? now() : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncStatusDates($session, $validated['status']);
            ActivityLog::record('delivery_route_sessions', 'created', 'Sesi rute dibuat', $session, [
                'route_code' => $session->route_code,
                'selling_mode' => $session->selling_mode,
                'status' => $session->status,
            ]);

            return $session;
        });

        return redirect()->route('delivery-route-sessions.edit', $session)
            ->with('success', 'Sesi rute berhasil ditambahkan');
    }

    public function show(DeliveryRouteSession $deliveryRouteSession)
    {
        $this->authorizeRouteSession($deliveryRouteSession);

        $deliveryRouteSession->load([
            'companyBranch',
            'salesTerritory',
            'salesperson.companyBranch',
            'driver.companyBranch',
            'vehicle.companyBranch',
            'orders.items.product',
            'orders.customer',
        ]);

        return view('delivery-route-sessions.show', [
            'session' => $deliveryRouteSession,
        ]);
    }

    public function edit(DeliveryRouteSession $deliveryRouteSession)
    {
        $this->authorizeRouteSession($deliveryRouteSession);
        abort_unless($deliveryRouteSession->canEdit(), 422, 'Sesi rute yang sudah ditutup tidak dapat diubah.');

        return view('delivery-route-sessions.edit', array_merge(
            $this->formData($deliveryRouteSession),
            ['session' => $deliveryRouteSession]
        ));
    }

    public function update(Request $request, DeliveryRouteSession $deliveryRouteSession)
    {
        $this->authorizeRouteSession($deliveryRouteSession);
        abort_unless($deliveryRouteSession->canEdit(), 422, 'Sesi rute yang sudah ditutup tidak dapat diubah.');

        $validated = $request->validate($this->rules($deliveryRouteSession));

        DB::transaction(function () use ($validated, $deliveryRouteSession) {
            $branch = $this->resolveBranch($validated['company_branch_id']);
            $routeDate = Carbon::parse($validated['route_date'])->toDateString();
            $routeCode = (
                (int) $deliveryRouteSession->company_branch_id === (int) $branch->id
                && $deliveryRouteSession->route_date?->toDateString() === $routeDate
            )
                ? $deliveryRouteSession->route_code
                : $this->generateRouteCode($branch, $routeDate, $deliveryRouteSession->id);

            $deliveryRouteSession->update([
                'company_branch_id' => $branch->id,
                'sales_territory_id' => $validated['sales_territory_id'],
                'salesperson_id' => $validated['salesperson_id'],
                'driver_id' => $validated['driver_id'],
                'delivery_vehicle_id' => $validated['delivery_vehicle_id'],
                'route_code' => $routeCode,
                'route_date' => $routeDate,
                'selling_mode' => $validated['selling_mode'],
                'status' => $validated['status'],
                'opening_qty' => $validated['opening_qty'] ?? 0,
                'sold_qty' => $validated['sold_qty'] ?? 0,
                'returned_qty' => $validated['returned_qty'] ?? 0,
                'damaged_qty' => $validated['damaged_qty'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncStatusDates($deliveryRouteSession, $validated['status']);
            ActivityLog::record('delivery_route_sessions', 'updated', 'Sesi rute diperbarui', $deliveryRouteSession, [
                'route_code' => $deliveryRouteSession->route_code,
                'selling_mode' => $deliveryRouteSession->selling_mode,
                'status' => $deliveryRouteSession->status,
            ]);
        });

        return redirect()->route('delivery-route-sessions.edit', $deliveryRouteSession)
            ->with('success', 'Sesi rute berhasil diperbarui');
    }

    private function rules(?DeliveryRouteSession $session = null): array
    {
        return [
            'company_branch_id' => ['required', 'exists:company_branches,id'],
            'sales_territory_id' => ['required', 'exists:sales_territories,id'],
            'salesperson_id' => ['required', 'exists:users,id'],
            'driver_id' => ['required', 'exists:users,id'],
            'delivery_vehicle_id' => ['required', 'exists:delivery_vehicles,id'],
            'route_date' => ['required', 'date'],
            'selling_mode' => ['required', Rule::in(array_keys(DeliveryRouteSession::MODE_LIST))],
            'status' => ['required', Rule::in(array_keys(DeliveryRouteSession::STATUS_LIST))],
            'opening_qty' => ['nullable', 'integer', 'min:0'],
            'sold_qty' => ['nullable', 'integer', 'min:0'],
            'returned_qty' => ['nullable', 'integer', 'min:0'],
            'damaged_qty' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function formData(?DeliveryRouteSession $session = null): array
    {
        $branchScopeId = $this->currentBranchScopeId();
        $defaultBranchId = $this->defaultBranchId();
        $selectedBranchId = (int) (old('company_branch_id', $session?->company_branch_id ?? $defaultBranchId) ?: 0);
        $selectedBranchId = $selectedBranchId ?: null;

        $companyBranches = $this->availableCompanyBranches();
        $territories = $this->availableTerritories($selectedBranchId, $session);
        $salespeople = $this->availableSalespeople($selectedBranchId, $session);
        $drivers = $this->availableDrivers($selectedBranchId, $session);
        $vehicles = $this->availableVehicles($selectedBranchId, $session);

        return compact(
            'companyBranches',
            'territories',
            'salespeople',
            'drivers',
            'vehicles',
            'branchScopeId',
            'defaultBranchId',
            'selectedBranchId'
        );
    }

    private function availableCompanyBranches()
    {
        $query = CompanyProfile::defaultProfile()->activeBranches();

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->whereKey($branchScopeId);
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }

    private function availableTerritories(?int $branchId, ?DeliveryRouteSession $session = null)
    {
        return SalesTerritory::query()
            ->active()
            ->with('companyBranch')
            ->when($branchId, fn ($query) => $query->forCompanyBranch($branchId))
            ->when($session?->sales_territory_id, fn ($query) => $query->orWhere('id', $session->sales_territory_id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function availableSalespeople(?int $branchId, ?DeliveryRouteSession $session = null)
    {
        return User::query()
            ->with('companyBranch')
            ->active()
            ->whereHas('roles', fn ($query) => $query->where('name', 'sales'))
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($salespersonQuery) use ($branchId) {
                    $salespersonQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $branchId);
                });
            })
            ->when($session?->salesperson_id, fn ($query) => $query->orWhere('id', $session->salesperson_id))
            ->orderBy('name')
            ->get();
    }

    private function availableDrivers(?int $branchId, ?DeliveryRouteSession $session = null)
    {
        return User::query()
            ->with('companyBranch')
            ->active()
            ->role('kurir')
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($driverQuery) use ($branchId) {
                    $driverQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $branchId);
                });
            })
            ->when($session?->driver_id, fn ($query) => $query->orWhere('id', $session->driver_id))
            ->orderBy('name')
            ->get();
    }

    private function availableVehicles(?int $branchId, ?DeliveryRouteSession $session = null)
    {
        return DeliveryVehicle::query()
            ->with('companyBranch')
            ->available()
            ->when($branchId, fn ($query) => $query->forCompanyBranch($branchId))
            ->when($session?->delivery_vehicle_id, fn ($query) => $query->orWhere('id', $session->delivery_vehicle_id))
            ->orderBy('code')
            ->get();
    }

    private function resolveBranch(int|string|null $branchId): CompanyBranch
    {
        $resolvedBranchId = $this->resolveBranchId($branchId ? (int) $branchId : null);

        return CompanyBranch::query()
            ->where('company_profile_id', CompanyProfile::defaultProfile()->id)
            ->whereKey($resolvedBranchId)
            ->firstOrFail();
    }

    private function resolveBranchId(?int $requestedBranchId): int
    {
        if ($branchScopeId = $this->currentBranchScopeId()) {
            return $branchScopeId;
        }

        if ($requestedBranchId) {
            return $requestedBranchId;
        }

        $defaultBranchId = $this->defaultBranchId();
        abort_if(!$defaultBranchId, 422, 'Cabang wajib dipilih.');

        return (int) $defaultBranchId;
    }

    private function defaultBranchId(): ?int
    {
        return CompanyProfile::defaultProfile()->defaultInvoiceBranch()?->id
            ?: CompanyProfile::defaultProfile()->activeBranches()->value('id');
    }

    private function currentBranchScopeId(): ?int
    {
        return auth()->user()?->scopedCompanyBranchId();
    }

    private function authorizeRouteSession(DeliveryRouteSession $session): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $session->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }

    private function syncStatusDates(DeliveryRouteSession $session, string $status): void
    {
        if ($status !== DeliveryRouteSession::STATUS_PLANNED && !$session->started_at) {
            $session->started_at = now();
        }

        if ($status === DeliveryRouteSession::STATUS_CLOSED && !$session->closed_at) {
            $session->closed_at = now();
        }

        $session->save();
    }

    private function shouldMarkStartedAt(string $status): bool
    {
        return $status !== DeliveryRouteSession::STATUS_PLANNED;
    }

    private function generateRouteCode(CompanyBranch $branch, ?string $routeDate = null, ?int $ignoreSessionId = null): string
    {
        $companyCode = CompanyProfile::normalizeCodePart(CompanyProfile::defaultProfile()->code ?: 'KMG', 'KMG');
        $branchCode = CompanyProfile::normalizeCodePart($branch->code ?: 'GLB', 'GLB');
        $datePart = $routeDate ? Carbon::parse($routeDate)->format('Ymd') : now()->format('Ymd');

        $sequence = 1;
        $query = DeliveryRouteSession::query()
            ->where('company_branch_id', $branch->id)
            ->whereDate('route_date', $routeDate ?: now()->toDateString());

        if ($ignoreSessionId) {
            $query->where('id', '!=', $ignoreSessionId);
        }

        if ($lastSession = $query->lockForUpdate()->orderByDesc('id')->first()) {
            $sequence = ((int) substr((string) $lastSession->route_code, -4)) + 1;
        }

        return 'RTS-' . $companyCode . $branchCode . $datePart . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function ensureUniqueRouteCode(string $routeCode): void
    {
        abort_if(DeliveryRouteSession::where('route_code', $routeCode)->exists(), 422, 'Kode sesi rute sudah digunakan.');
    }
}
