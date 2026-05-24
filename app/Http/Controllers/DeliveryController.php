<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
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
        $query = Delivery::with('order', 'kurir');
        
        // Search by order number or kurir
        if ($request->filled('search')) {
            $query->whereHas('order', function($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%");
            })->orWhereHas('kurir', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
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
        $kurirs = User::role('kurir')->active()->orderBy('name')->get();
        
        return view('deliveries.index', compact('deliveries', 'statuses', 'kurirs'));
    }

    /**
     * Show the form for creating a new delivery.
     */
    public function create()
    {
        $orders = Order::where('status', Order::STATUS_READY)
            ->whereDoesntHave('delivery')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $kurirs = User::role('kurir')->active()->orderBy('name')->get();
        
        return view('deliveries.create', compact('orders', 'kurirs'));
    }

    /**
     * Store a newly created delivery in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'kurir_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $delivery = DB::transaction(function () use ($validated) {
                $order = Order::lockForUpdate()->findOrFail($validated['order_id']);

                if ($order->status !== Order::STATUS_READY) {
                    throw new \Exception('Order harus berstatus siap kirim sebelum dibuatkan pengiriman');
                }

                if (Delivery::where('order_id', $order->id)->exists()) {
                    throw new \Exception('Order sudah memiliki pengiriman');
                }

                $delivery = Delivery::create([
                    'order_id' => $order->id,
                    'kurir_id' => $validated['kurir_id'],
                    'status' => Delivery::STATUS_ASSIGNED,
                    'assigned_at' => now(),
                    'notes' => $validated['notes'],
                ]);

                $order->updateStatus(Order::STATUS_SHIPPED, 'Pengiriman diassign ke kurir');

                return $delivery;
            });

            return redirect()->route('deliveries.show', $delivery)
                ->with('success', 'Pengiriman berhasil diassign');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat pengiriman: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified delivery.
     */
    public function show(Delivery $delivery)
    {
        $delivery->load('order.user', 'kurir', 'order.items.product');
        
        return view('deliveries.show', compact('delivery'));
    }

    /**
     * Show the form for editing the specified delivery.
     */
    public function edit(Delivery $delivery)
    {
        $orders = Order::where('status', Order::STATUS_READY)
            ->whereDoesntHave('delivery')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $kurirs = User::role('kurir')->active()->orderBy('name')->get();
        
        return view('deliveries.edit', compact('delivery', 'orders', 'kurirs'));
    }

    /**
     * Update the specified delivery in storage.
     */
    public function update(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'kurir_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);
        
        $delivery->update([
            'kurir_id' => $validated['kurir_id'],
            'notes' => $validated['notes'],
        ]);
        
        return redirect()->route('deliveries.show', $delivery)
            ->with('success', 'Pengiriman berhasil diupdate');
    }

    /**
     * Remove the specified delivery from storage.
     */
    public function destroy(Delivery $delivery)
    {
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
                    break;
                case Delivery::STATUS_IN_TRANSIT:
                    $delivery->in_transit_at = now();
                    break;
                case Delivery::STATUS_COMPLETED:
                    $delivery->completed_at = now();
                    // Update order status to delivered
                    $delivery->order->updateStatus(Order::STATUS_DELIVERED, 'Barang diterima customer');
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
}
