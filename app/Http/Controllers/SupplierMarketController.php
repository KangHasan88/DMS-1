<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierMarket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierMarketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierMarket::query()->search($request->search);

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $perPage = $request->get('per_page', 10);
        $markets = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);

        return view('supplier-markets.index', compact('markets'));
    }

    public function create()
    {
        return view('supplier-markets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:supplier_markets,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = SupplierMarket::makeUniqueSlug($validated['name']);
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        SupplierMarket::create($validated);

        return redirect()->to($request->input('redirect_to', route('supplier-markets.index')))
            ->with('success', 'Pasar pemasok berhasil ditambahkan');
    }

    public function edit(SupplierMarket $supplierMarket)
    {
        return view('supplier-markets.edit', compact('supplierMarket'));
    }

    public function update(Request $request, SupplierMarket $supplierMarket)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('supplier_markets', 'name')->ignore($supplierMarket->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $supplierMarket, $validated) {
            $oldName = $supplierMarket->name;

            $supplierMarket->update([
                'name' => $validated['name'],
                'slug' => SupplierMarket::makeUniqueSlug($validated['name'], $supplierMarket->id),
                'description' => $validated['description'] ?? null,
                'is_active' => $request->has('is_active'),
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            if ($oldName !== $supplierMarket->name) {
                Supplier::where('market_name', $oldName)->update(['market_name' => $supplierMarket->name]);
            }
        });

        return redirect()->route('supplier-markets.index')
            ->with('success', 'Pasar pemasok berhasil diupdate');
    }

    public function destroy(SupplierMarket $supplierMarket)
    {
        $suppliersCount = $supplierMarket->suppliersCount();

        if ($suppliersCount > 0) {
            return redirect()->route('supplier-markets.index')
                ->with('error', "Pasar tidak dapat dihapus karena digunakan oleh {$suppliersCount} pemasok");
        }

        $supplierMarket->delete();

        return redirect()->route('supplier-markets.index')
            ->with('success', 'Pasar pemasok berhasil dihapus');
    }

    public function toggleStatus(SupplierMarket $supplierMarket)
    {
        $supplierMarket->update(['is_active' => !$supplierMarket->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $supplierMarket->is_active,
            'message' => $supplierMarket->is_active ? 'Pasar pemasok diaktifkan' : 'Pasar pemasok dinonaktifkan',
        ]);
    }
}
