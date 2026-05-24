<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();
        
        // Search
        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%")
                    ->orWhere('market_name', 'like', "%{$request->search}%")
                    ->orWhere('stall_number', 'like', "%{$request->search}%");
            });
        }
        
        // Filter by category
        if ($request->filled('category')) {
            if ($request->category == 'all') {
                $query->where('category', 'all');
            } else {
                $query->where(function($q) use ($request) {
                    $q->where('category', $request->category)
                      ->orWhere('category', 'all');
                });
            }
        }
        
        // Filter by market
        if ($request->filled('market')) {
            $query->where('market_name', 'like', "%{$request->market}%");
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        $perPage = $request->get('per_page', 10);
        $suppliers = $query->orderBy('name')->paginate($perPage);
        
        // Get categories for filter
        $categories = Supplier::CATEGORIES;
        
        return view('suppliers.index', compact('suppliers', 'categories'));
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create()
    {
        $categories = Supplier::CATEGORIES;
        return view('suppliers.create', compact('categories'));
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:suppliers,phone',
            'alternate_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:suppliers,email',
            'market_name' => 'nullable|string|max:255',
            'stall_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'category' => 'required|in:' . implode(',', array_keys(Supplier::CATEGORIES)),
            'specialty' => 'nullable|string|max:255',
            'min_order' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'payment_notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        $validated['is_active'] = $request->has('is_active');
        
        Supplier::create($validated);
        
        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil ditambahkan');
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier)
    {
        $totalTransactions = $supplier->total_transactions;
        $totalPurchase = $supplier->total_purchase;
        $lastPurchase = $supplier->last_purchase_at;
        
        return view('suppliers.show', compact('supplier', 'totalTransactions', 'totalPurchase', 'lastPurchase'));
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Supplier $supplier)
    {
        $categories = Supplier::CATEGORIES;
        return view('suppliers.edit', compact('supplier', 'categories'));
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:suppliers,phone,' . $supplier->id,
            'alternate_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:suppliers,email,' . $supplier->id,
            'market_name' => 'nullable|string|max:255',
            'stall_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'category' => 'required|in:' . implode(',', array_keys(Supplier::CATEGORIES)),
            'specialty' => 'nullable|string|max:255',
            'min_order' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'payment_notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        $validated['is_active'] = $request->has('is_active');
        
        $supplier->update($validated);
        
        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil diupdate');
    }

    /**
     * Remove the specified supplier from storage.
     */
    public function destroy(Supplier $supplier)
    {
        if (
            $supplier->purchaseOrders()->exists()
            || $supplier->directPurchases()->exists()
            || $supplier->consignments()->exists()
        ) {
            return back()->with('error', 'Supplier tidak dapat dihapus karena memiliki riwayat pembelian');
        }
        
        $supplier->delete();
        
        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil dihapus');
    }
    
    /**
     * Toggle supplier status.
     */
    public function toggleStatus(Supplier $supplier)
    {
        $newStatus = !$supplier->is_active;
        $supplier->update(['is_active' => $newStatus]);
        
        return response()->json([
            'success' => true,
            'is_active' => $newStatus,
            'message' => $newStatus ? 'Supplier diaktifkan' : 'Supplier dinonaktifkan'
        ]);
    }
}
