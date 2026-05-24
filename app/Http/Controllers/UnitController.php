<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the units.
     */
    public function index(Request $request)
    {
        $query = Unit::query();
        
        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        // Filter by category
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        $perPage = $request->get('per_page', 10);
        $units = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);
        
        $categories = Unit::select('category')->distinct()->whereNotNull('category')->pluck('category');
        
        return view('units.index', compact('units', 'categories'));
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create()
    {
        return view('units.create');
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:units,name',
            'code' => 'required|string|max:20|unique:units,code',
            'symbol' => 'nullable|string|max:10',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);
        
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $request->sort_order ?? 0;
        
        Unit::create($validated);
        
        return redirect()->route('units.index')
            ->with('success', 'Satuan berhasil ditambahkan');
    }

    /**
     * Display the specified unit.
     */
    public function show(Unit $unit)
    {
        $productsCount = $unit->products()->count();
        return view('units.show', compact('unit', 'productsCount'));
    }

    /**
     * Show the form for editing the specified unit.
     */
    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:units,name,' . $unit->id,
            'code' => 'required|string|max:20|unique:units,code,' . $unit->id,
            'symbol' => 'nullable|string|max:10',
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);
        
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $request->sort_order ?? 0;
        
        $unit->update($validated);
        
        return redirect()->route('units.index')
            ->with('success', 'Satuan berhasil diupdate');
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy(Unit $unit)
    {
        // Cek apakah unit sedang digunakan
        if ($unit->products()->count() > 0) {
            return redirect()->route('units.index')
                ->with('error', 'Satuan tidak dapat dihapus karena sedang digunakan oleh ' . $unit->products()->count() . ' produk');
        }
        
        $unit->delete();
        
        return redirect()->route('units.index')
            ->with('success', 'Satuan berhasil dihapus');
    }
    
    /**
     * Toggle unit status.
     */
    public function toggleStatus(Unit $unit)
    {
        $unit->update(['is_active' => !$unit->is_active]);
        
        return response()->json([
            'success' => true,
            'is_active' => $unit->is_active,
            'message' => $unit->is_active ? 'Satuan diaktifkan' : 'Satuan dinonaktifkan'
        ]);
    }
    
    /**
     * Get units list for API (for select dropdown)
     */
    public function getList()
    {
        $units = Unit::active()->orderBy('sort_order')->orderBy('name')->get();
        
        return response()->json($units);
    }
}