<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\DeliveryVendor;
use App\Models\DeliveryVehicle;
use App\Models\DriverVehicleAssignment;
use App\Models\ActivityLog;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * Display a listing of deliveries.
     */
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;
        $query = Delivery::with('order.companyBranch', 'order.user', 'kurir', 'vendor', 'vehicle');

        if ($this->isKurirOnly()) {
            $query->where('kurir_id', auth()->id());
        } elseif ($branchScopeId) {
            $query->whereHas('order', function ($orderQuery) use ($branchScopeId) {
                $orderQuery->where('company_branch_id', $branchScopeId);
            });
        } elseif ($request->filled('company_branch_id')) {
            $query->whereHas('order', function ($orderQuery) use ($request) {
                $orderQuery->where('company_branch_id', $request->company_branch_id);
            });
        }
        
        // Search by order number, kurir, vendor, or resi
        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->whereHas('order', function($q) use ($request) {
                    $q->where('order_number', 'like', "%{$request->search}%");
                })->orWhereHas('kurir', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
                })->orWhereHas('vendor', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
                })->orWhere('tracking_code', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('delivery_method')) {
            $query->where('delivery_method', $request->delivery_method);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by kurir
        if ($request->filled('kurir_id')) {
            $query->where('kurir_id', $request->kurir_id);
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $perPage = $request->get('per_page', 10);
        $deliveries = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        $statuses = Delivery::STATUS_LIST;
        $kurirs = $this->availableKurirsQuery()
            ->with('activeDriverVehicleAssignment.vehicle')
            ->orderBy('name')
            ->get();
        $companyBranches = $this->availableCompanyBranches();
        $deliveryMethods = Delivery::METHOD_LIST;
        
        return view('deliveries.index', compact('deliveries', 'statuses', 'kurirs', 'companyBranches', 'canFilterBranches', 'deliveryMethods'));
    }

    /**
     * Show the form for creating a new delivery.
     */
    public function create()
    {
        $this->authorizeDeliveryProcessor();

        $orders = $this->availableReadyOrdersQuery()
            ->with([
                'shippingAddress.deliveryZone.activeDepots',
            ])
            ->whereDoesntHave('delivery')
            ->orderBy('created_at', 'desc')
            ->get();

        $orderItems = DB::table('order_items')
            ->whereIn('order_id', $orders->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('order_id');

        $scheduledDeliveries = Delivery::with('order:id,order_number,delivery_date,delivery_time_slot')
            ->whereNotNull('delivery_vehicle_id')
            ->whereNotIn('status', [Delivery::STATUS_COMPLETED, Delivery::STATUS_CANCELLED])
            ->whereHas('order', function ($query) use ($orders) {
                $query->whereIn('delivery_date', $orders->pluck('delivery_date')->filter()->map->toDateString()->unique());
            })
            ->get();

        $orderDetails = $orders->mapWithKeys(function (Order $order) use ($orderItems, $scheduledDeliveries) {
            $deliveryZone = $order->shippingAddress?->deliveryZone;
            $activeDepots = $deliveryZone?->activeDepots ?? collect();
            $recommendedDepot = $activeDepots->first();

            return [
                $order->id => [
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user->name ?? '-',
                    'customer_phone' => $order->user->phone ?? '-',
                    'branch_id' => $order->company_branch_id,
                    'branch' => trim(($order->companyBranch->name ?? '-') . ' - ' . ($order->companyBranch->code ?? '-')),
                    'delivery_date' => $order->delivery_date ? $order->delivery_date->format('d M Y') : '-',
                    'delivery_time_slot' => $order->delivery_time_slot ?: '-',
                    'shipping_address' => $order->shipping_address_snapshot ?: $order->address ?: '-',
                    'delivery_zone' => $deliveryZone ? trim($deliveryZone->code . ' - ' . $deliveryZone->name) : null,
                    'coverage_verified' => (bool) $order->shippingAddress?->coverage_verified_at,
                    'recommended_depot' => $recommendedDepot
                        ? trim($recommendedDepot->name . ' - ' . $recommendedDepot->code)
                        : null,
                    'depot_options' => $activeDepots->map(fn (CompanyBranch $depot) => [
                        'id' => $depot->id,
                        'label' => trim($depot->name . ' - ' . $depot->code),
                        'priority' => (int) $depot->pivot->priority,
                        'max_daily_orders' => $depot->pivot->max_daily_orders,
                    ])->values(),
                    'payment_timing' => $order->payment_timing === 'prepaid' ? 'Pre-paid' : 'Post-paid',
                    'fulfillment_type' => $order->fulfillment_type === 'jit' ? 'BLJ' : 'Stock',
                    'delivery_fee' => (int) ($order->delivery_fee ?? 0),
                    'grand_total' => (int) ($order->grand_total ?? $order->total ?? 0),
                    'unavailable_vehicle_ids' => $scheduledDeliveries
                        ->filter(fn (Delivery $delivery) => $this->deliverySchedulesOverlap($order, $delivery->order))
                        ->pluck('delivery_vehicle_id')
                        ->filter()
                        ->unique()
                        ->values(),
                    'items' => ($orderItems[$order->id] ?? collect())->map(fn ($item) => [
                        'name' => $item->product_name,
                        'quantity' => (int) $item->quantity,
                        'price' => (int) $item->price,
                        'subtotal' => (int) $item->subtotal,
                    ])->values(),
                ],
            ];
        });
        
        $kurirs = $this->availableKurirsQuery()->orderBy('name')->get();
        $deliveryVendors = $this->availableDeliveryVendorsQuery()->orderBy('name')->get();
        $deliveryVehicles = $this->availableDeliveryVehiclesQuery()->orderBy('code')->get();
        
        return view('deliveries.create', compact('orders', 'kurirs', 'deliveryVendors', 'deliveryVehicles', 'orderDetails'));
    }

    /**
     * Store a newly created delivery in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeDeliveryProcessor();

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'delivery_method' => 'required|in:' . implode(',', array_keys(Delivery::METHOD_LIST)),
            'kurir_id' => 'nullable|exists:users,id',
            'delivery_vehicle_id' => 'nullable|exists:delivery_vehicles,id',
            'vehicle_override_reason' => 'nullable|string|max:255',
            'delivery_vendor_id' => 'nullable|exists:delivery_vendors,id',
            'tracking_code' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $delivery = DB::transaction(function () use ($validated) {
                $order = Order::lockForUpdate()->findOrFail($validated['order_id']);
                $deliveryMethod = $validated['delivery_method'];
                $kurir = null;
                $vehicle = null;
                $vendor = null;

                if ($deliveryMethod === Delivery::METHOD_INTERNAL) {
                    if (empty($validated['kurir_id'])) {
                        throw new \Exception('Kurir wajib dipilih untuk pengiriman internal');
                    }

                    $kurir = User::with('activeDriverVehicleAssignment.vehicle')
                        ->role('kurir')
                        ->active()
                        ->findOrFail($validated['kurir_id']);
                    $this->ensureDeliveryBranchAccess($order, $kurir);

                    [$vehicle, $overrideReason] = $this->resolveInternalVehicle(
                        $order,
                        $kurir,
                        $validated['delivery_vehicle_id'] ?? null,
                        $validated['vehicle_override_reason'] ?? null
                    );
                }

                if ($deliveryMethod === Delivery::METHOD_EXPEDITION) {
                    if (empty($validated['delivery_vendor_id'])) {
                        throw new \Exception('Vendor ekspedisi wajib dipilih');
                    }

                    $vendor = DeliveryVendor::active()->findOrFail($validated['delivery_vendor_id']);
                    $this->ensureVendorBranchAccess($order, $vendor);
                }

                if ($order->status !== Order::STATUS_READY) {
                    throw new \Exception('Order harus berstatus siap kirim sebelum dibuatkan pengiriman');
                }

                if (Delivery::where('order_id', $order->id)->exists()) {
                    throw new \Exception('Order sudah memiliki pengiriman');
                }

                $delivery = Delivery::create([
                    'order_id' => $order->id,
                    'delivery_method' => $deliveryMethod,
                    'delivery_vendor_id' => $vendor?->id,
                    'kurir_id' => $kurir?->id,
                    'delivery_vehicle_id' => $vehicle?->id,
                    'vehicle_override_reason' => $overrideReason ?? null,
                    'status' => Delivery::STATUS_ASSIGNED,
                    'assigned_at' => now(),
                    'tracking_code' => $validated['tracking_code'] ?? null,
                    'actual_shipping_cost' => 0,
                    'shipping_cost_status' => $deliveryMethod === Delivery::METHOD_EXPEDITION
                        ? Delivery::COST_UNBILLED
                        : Delivery::COST_NOT_APPLICABLE,
                    'notes' => $validated['notes'] ?? null,
                ]);

                if (!empty($validated['tracking_code'])) {
                    $order->tracking_code = $validated['tracking_code'];
                }

                $order->admin_notes = ($order->admin_notes ? $order->admin_notes . "\n" : '') .
                    now()->format('d/m/Y H:i') . ' - Pengiriman ditugaskan via ' . $delivery->delivery_method_label;
                $order->save();

                if (!empty($overrideReason)) {
                    ActivityLog::record('deliveries', 'vehicle_overridden', 'Armada utama diganti saat penugasan', $delivery, [
                        'driver_id' => $kurir?->id,
                        'primary_vehicle_id' => $kurir?->activeDriverVehicleAssignment?->delivery_vehicle_id,
                        'assigned_vehicle_id' => $vehicle?->id,
                        'reason' => $overrideReason,
                    ]);
                }

                return $delivery;
            });

            return redirect()->route('deliveries.show', $delivery)
                ->with('success', 'Pengiriman berhasil ditugaskan');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat pengiriman: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified delivery.
     */
    public function show(Delivery $delivery)
    {
        $this->authorizeKurirDelivery($delivery);
        $this->authorizeBranchDelivery($delivery);

        $delivery->load('order.user', 'order.companyBranch', 'kurir', 'vendor', 'vehicle', 'order.items.product');
        
        return view('deliveries.show', compact('delivery'));
    }

    /**
     * Show the form for editing the specified delivery.
     */
    public function edit(Delivery $delivery)
    {
        $this->authorizeDeliveryProcessor();
        $this->authorizeKurirDelivery($delivery);
        $this->authorizeBranchDelivery($delivery);

        $orders = $this->availableReadyOrdersQuery()
            ->whereDoesntHave('delivery')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $kurirs = $this->availableKurirsQuery()
            ->with('activeDriverVehicleAssignment.vehicle')
            ->orderBy('name')
            ->get();
        $deliveryVendors = $this->availableDeliveryVendorsQuery()->orderBy('name')->get();
        $deliveryVehicles = $this->availableDeliveryVehiclesQuery($delivery->order?->company_branch_id, $delivery->delivery_vehicle_id)->orderBy('code')->get();
        
        return view('deliveries.edit', compact('delivery', 'orders', 'kurirs', 'deliveryVendors', 'deliveryVehicles'));
    }

    /**
     * Update the specified delivery in storage.
     */
    public function update(Request $request, Delivery $delivery)
    {
        $this->authorizeDeliveryProcessor();
        $this->authorizeKurirDelivery($delivery);
        $this->authorizeBranchDelivery($delivery);

        $validated = $request->validate([
            'kurir_id' => 'nullable|exists:users,id',
            'delivery_vehicle_id' => 'nullable|exists:delivery_vehicles,id',
            'vehicle_override_reason' => 'nullable|string|max:255',
            'tracking_code' => 'nullable|string|max:100',
            'actual_shipping_cost' => 'nullable|numeric|min:0',
            'shipping_cost_status' => 'nullable|in:' . implode(',', array_keys(Delivery::COST_STATUS_LIST)),
            'vendor_invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated, $delivery) {
                $delivery = Delivery::with('order')->lockForUpdate()->findOrFail($delivery->id);
                $oldDriverId = $delivery->kurir_id;
                $oldVehicleId = $delivery->delivery_vehicle_id;

                if ($delivery->usesInternalDelivery() && empty($validated['kurir_id'])) {
                    throw new \Exception('Driver wajib dipilih untuk pengiriman internal');
                }

                $kurir = $delivery->usesInternalDelivery() && !empty($validated['kurir_id'])
                    ? User::with('activeDriverVehicleAssignment.vehicle')->role('kurir')->active()->findOrFail($validated['kurir_id'])
                    : null;

                if ($kurir) {
                    $this->ensureDeliveryBranchAccess($delivery->order, $kurir);
                }

                [$vehicle, $overrideReason] = $delivery->usesInternalDelivery()
                    ? $this->resolveInternalVehicle(
                        $delivery->order,
                        $kurir,
                        $validated['delivery_vehicle_id'] ?? null,
                        $validated['vehicle_override_reason'] ?? null,
                        $delivery->id
                    )
                    : [null, null];

                $newDriverId = $kurir?->id ?? $delivery->kurir_id;
                $newVehicleId = $delivery->usesInternalDelivery() ? $vehicle?->id : null;
                $assignmentChanged = $delivery->usesInternalDelivery() && (
                    (int) $oldDriverId !== (int) $newDriverId
                    || (int) $oldVehicleId !== (int) $newVehicleId
                );
                $changeReason = trim((string) ($validated['vehicle_override_reason'] ?? ''));

                if ($assignmentChanged && $delivery->status !== Delivery::STATUS_ASSIGNED) {
                    throw new \Exception('Driver atau armada hanya dapat diubah sebelum barang diambil.');
                }

                if ($assignmentChanged && $changeReason === '') {
                    throw new \Exception('Alasan perubahan penugasan wajib diisi.');
                }

                $delivery->update([
                    'kurir_id' => $newDriverId,
                    'delivery_vehicle_id' => $newVehicleId,
                    'vehicle_override_reason' => $delivery->usesInternalDelivery() ? $overrideReason : null,
                    'tracking_code' => $validated['tracking_code'] ?? $delivery->tracking_code,
                    'actual_shipping_cost' => (int) ($validated['actual_shipping_cost'] ?? $delivery->actual_shipping_cost ?? 0),
                    'shipping_cost_status' => $delivery->usesExpedition()
                        ? ($validated['shipping_cost_status'] ?? $delivery->shipping_cost_status)
                        : Delivery::COST_NOT_APPLICABLE,
                    'vendor_invoice_number' => $delivery->usesExpedition()
                        ? ($validated['vendor_invoice_number'] ?? $delivery->vendor_invoice_number)
                        : null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($assignmentChanged) {
                    ActivityLog::record('deliveries', 'assignment_changed', 'Driver atau armada pengiriman diubah', $delivery, [
                        'old_driver_id' => $oldDriverId,
                        'new_driver_id' => $delivery->kurir_id,
                        'old_vehicle_id' => $oldVehicleId,
                        'new_vehicle_id' => $delivery->delivery_vehicle_id,
                        'reason' => $changeReason,
                    ]);
                }
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui pengiriman: ' . $e->getMessage());
        }
        
        return redirect()->route('deliveries.show', $delivery)
            ->with('success', 'Pengiriman berhasil diupdate');
    }

    /**
     * Remove the specified delivery from storage.
     */
    public function destroy(Delivery $delivery)
    {
        $this->authorizeDeliveryProcessor();
        $this->authorizeKurirDelivery($delivery);
        $this->authorizeBranchDelivery($delivery);

        if ($delivery->status !== Delivery::STATUS_ASSIGNED) {
            return back()->with('error', 'Pengiriman tidak dapat dihapus karena sudah diproses');
        }
        
        $delivery->delete();
        
        return redirect()->route('deliveries.index')
            ->with('success', 'Pengiriman berhasil dihapus');
    }
    
    /**
     * Update delivery status.
     */
    public function updateStatus(Request $request, Delivery $delivery)
    {
        $this->authorizeDeliveryProcessor();
        $this->authorizeKurirDelivery($delivery);
        $this->authorizeBranchDelivery($delivery);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Delivery::STATUS_LIST)),
            'proof_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $oldStatus = $delivery->status;

            $allowedTransitions = [
                Delivery::STATUS_ASSIGNED => [Delivery::STATUS_PICKED_UP],
                Delivery::STATUS_PICKED_UP => [Delivery::STATUS_IN_TRANSIT],
                Delivery::STATUS_IN_TRANSIT => [Delivery::STATUS_COMPLETED],
                Delivery::STATUS_COMPLETED => [],
            ];

            if (!in_array($validated['status'], $allowedTransitions[$oldStatus] ?? [], true)) {
                throw new \Exception('Perubahan status pengiriman tidak valid');
            }

            $delivery->status = $validated['status'];
            
            // Update timestamp based on status
            switch ($validated['status']) {
                case Delivery::STATUS_PICKED_UP:
                    $delivery->picked_up_at = now();
                    $delivery->order->updateStatus(Order::STATUS_SHIPPED, $delivery->usesExpedition()
                        ? 'Barang diserahkan ke ekspedisi dan dalam pengiriman'
                        : 'Barang diambil kurir dan dalam pengiriman');
                    break;
                case Delivery::STATUS_IN_TRANSIT:
                    $delivery->in_transit_at = now();
                    $delivery->order->updateStatus(Order::STATUS_SHIPPED, 'Barang sedang dalam pengiriman');
                    break;
                case Delivery::STATUS_COMPLETED:
                    $delivery->completed_at = now();
                    if ($delivery->order->isPostPaid()) {
                        $delivery->order->admin_notes = ($delivery->order->admin_notes ? $delivery->order->admin_notes . "\n" : '') .
                            now()->format('d/m/Y H:i') . ' - Pengiriman selesai, menunggu pembayaran';
                        $delivery->order->save();
                    } else {
                        $delivery->order->updateStatus(Order::STATUS_DELIVERED, 'Barang diterima customer');
                    }
                    break;
            }
            
            // Update location if provided
            if ($request->filled('latitude')) {
                $delivery->latitude = $request->latitude;
                $delivery->longitude = $request->longitude;
            }
            
            // Upload proof image
            if ($request->hasFile('proof_image')) {
                $path = $request->file('proof_image')->store('deliveries', 'public');
                $delivery->proof_image = $path;
            }
            
            if ($request->filled('notes')) {
                $delivery->notes = $request->notes;
            }
            
            $delivery->save();

            ActivityLog::record('deliveries', 'status_changed', 'Status pengiriman berubah', $delivery, [
                'delivery_id' => $delivery->id,
                'order_id' => $delivery->order_id,
                'kurir_id' => $delivery->kurir_id,
                'delivery_vehicle_id' => $delivery->delivery_vehicle_id,
                'old_status' => $oldStatus,
                'new_status' => $delivery->status,
                'notes' => $request->input('notes'),
                'latitude' => $delivery->latitude,
                'longitude' => $delivery->longitude,
            ]);
            
            DB::commit();
            
            return redirect()->route('deliveries.show', $delivery)
                ->with('success', 'Status pengiriman berhasil diupdate menjadi ' . Delivery::STATUS_LIST[$validated['status']]);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate status: ' . $e->getMessage());
        }
    }
    
    /**
     * Get deliveries for kurir (mobile view).
     */
    public function kurirToday(Request $request)
    {
        $kurirId = auth()->id();
        
        $deliveries = Delivery::where('kurir_id', $kurirId)
            ->whereDate('created_at', today())
            ->with('order.user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('deliveries.kurir-today', compact('deliveries'));
    }
    
    /**
     * Update location from kurir.
     */
    public function updateLocation(Request $request, Delivery $delivery)
    {
        $this->authorizeDeliveryProcessor();
        $this->authorizeKurirDelivery($delivery);
        $this->authorizeBranchDelivery($delivery);

        $validated = $request->validate([
            'latitude' => 'required|string',
            'longitude' => 'required|string',
        ]);
        
        $delivery->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Lokasi berhasil diupdate'
        ]);
    }

    private function isKurirOnly(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('kurir') && !$user->hasAnyRole(['super-admin', 'admin', 'manager']);
    }

    private function authorizeKurirDelivery(Delivery $delivery): void
    {
        if ($this->isKurirOnly() && $delivery->kurir_id !== auth()->id()) {
            abort(403);
        }
    }

    private function authorizeBranchDelivery(Delivery $delivery): void
    {
        if ($this->isKurirOnly()) {
            return;
        }

        $branchScopeId = $this->currentBranchScopeId();

        if ($branchScopeId && (int) $delivery->order?->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }

    private function authorizeDeliveryProcessor(): void
    {
        $user = auth()->user();

        if (!$user || !$user->canProcessDelivery()) {
            abort(403);
        }
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

        return $query->get();
    }

    private function availableReadyOrdersQuery()
    {
        $query = Order::with('user', 'companyBranch')->where('status', Order::STATUS_READY);

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where('company_branch_id', $branchScopeId);
        }

        return $query;
    }

    private function availableKurirsQuery()
    {
        $query = User::with('companyBranch')->role('kurir')->active();

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where('company_branch_id', $branchScopeId);
        }

        return $query;
    }

    private function availableDeliveryVendorsQuery()
    {
        return DeliveryVendor::with('companyBranch')
            ->active()
            ->forCompanyBranch($this->currentBranchScopeId());
    }

    private function availableDeliveryVehiclesQuery(?int $companyBranchId = null, ?int $currentVehicleId = null)
    {
        $branchId = $companyBranchId ?: $this->currentBranchScopeId();

        return DeliveryVehicle::with('companyBranch')
            ->active()
            ->forCompanyBranch($branchId)
            ->where(function ($query) use ($currentVehicleId) {
                $query->where('status', DeliveryVehicle::STATUS_AVAILABLE);

                if ($currentVehicleId) {
                    $query->orWhere('id', $currentVehicleId);
                }
            });
    }

    private function ensureDeliveryBranchAccess(Order $order, User $kurir): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $order->company_branch_id !== $branchScopeId) {
            throw new \Exception('Order tidak terdaftar pada cabang user.');
        }

        if ($kurir->company_branch_id && $order->company_branch_id && (int) $kurir->company_branch_id !== (int) $order->company_branch_id) {
            throw new \Exception('Kurir harus berada pada cabang yang sama dengan order.');
        }
    }

    private function ensureVendorBranchAccess(Order $order, DeliveryVendor $vendor): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $order->company_branch_id !== $branchScopeId) {
            throw new \Exception('Order tidak terdaftar pada cabang user.');
        }

        if ($vendor->company_branch_id && $order->company_branch_id && (int) $vendor->company_branch_id !== (int) $order->company_branch_id) {
            throw new \Exception('Vendor ekspedisi harus berada pada cabang yang sama dengan order.');
        }
    }

    private function ensureVehicleBranchAccess(Order $order, DeliveryVehicle $vehicle): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $order->company_branch_id !== $branchScopeId) {
            throw new \Exception('Order tidak terdaftar pada cabang user.');
        }

        if ($vehicle->company_branch_id && $order->company_branch_id && (int) $vehicle->company_branch_id !== (int) $order->company_branch_id) {
            throw new \Exception('Armada harus berada pada cabang yang sama dengan order.');
        }
    }

    private function ensureVehicleScheduleAvailable(Order $order, DeliveryVehicle $vehicle, ?int $ignoreDeliveryId = null): void
    {
        if (!$order->delivery_date || !$this->parseDeliveryTimeSlot($order->delivery_time_slot)) {
            return;
        }

        $scheduledDeliveries = Delivery::with('order:id,order_number,delivery_date,delivery_time_slot')
            ->where('delivery_vehicle_id', $vehicle->id)
            ->whereNotIn('status', [Delivery::STATUS_COMPLETED, Delivery::STATUS_CANCELLED])
            ->when($ignoreDeliveryId, fn ($query) => $query->whereKeyNot($ignoreDeliveryId))
            ->whereHas('order', fn ($query) => $query->whereDate('delivery_date', $order->delivery_date))
            ->lockForUpdate()
            ->get();

        $conflict = $scheduledDeliveries
            ->first(fn (Delivery $delivery) => $this->deliverySchedulesOverlap($order, $delivery->order));

        if ($conflict) {
            $slot = $conflict->order?->delivery_time_slot ?: 'slot yang sama';
            $orderNumber = $conflict->order?->order_number ?: '-';

            throw new \Exception(
                "Armada {$vehicle->code} sudah dijadwalkan untuk order {$orderNumber} pada {$slot}. Pilih armada backup."
            );
        }
    }

    private function resolveInternalVehicle(
        Order $order,
        User $driver,
        ?int $requestedVehicleId,
        ?string $overrideReason,
        ?int $ignoreDeliveryId = null
    ): array {
        $primaryVehicle = $driver->activeDriverVehicleAssignment?->vehicle;
        $selectedVehicleId = $requestedVehicleId ?: $primaryVehicle?->id;

        if (!$selectedVehicleId) {
            throw new \Exception('Driver belum memiliki armada utama. Tetapkan armada utama atau pilih armada manual.');
        }

        $vehicle = DeliveryVehicle::available()->findOrFail($selectedVehicleId);
        $this->ensureVehicleBranchAccess($order, $vehicle);
        $this->ensureVehicleScheduleAvailable($order, $vehicle, $ignoreDeliveryId);

        $isOverride = $primaryVehicle && (int) $primaryVehicle->id !== (int) $vehicle->id;

        if ($isOverride && blank($overrideReason)) {
            throw new \Exception('Alasan penggantian armada wajib diisi.');
        }

        return [$vehicle, $isOverride ? trim((string) $overrideReason) : null];
    }

    private function deliverySchedulesOverlap(Order $candidate, ?Order $scheduled): bool
    {
        if (!$scheduled || !$candidate->delivery_date || !$scheduled->delivery_date) {
            return false;
        }

        if (!$candidate->delivery_date->isSameDay($scheduled->delivery_date)) {
            return false;
        }

        $candidateSlot = $this->parseDeliveryTimeSlot($candidate->delivery_time_slot);
        $scheduledSlot = $this->parseDeliveryTimeSlot($scheduled->delivery_time_slot);

        if (!$candidateSlot || !$scheduledSlot) {
            return false;
        }

        return $candidateSlot[0] < $scheduledSlot[1] && $scheduledSlot[0] < $candidateSlot[1];
    }

    private function parseDeliveryTimeSlot(?string $timeSlot): ?array
    {
        if (!$timeSlot || !preg_match('/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/', $timeSlot, $matches)) {
            return null;
        }

        return [
            ((int) $matches[1] * 60) + (int) $matches[2],
            ((int) $matches[3] * 60) + (int) $matches[4],
        ];
    }
}
