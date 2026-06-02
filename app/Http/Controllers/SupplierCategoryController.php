<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierCategory::query()->search($request->search);

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $perPage = $request->get('per_page', 10);
        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);

        return view('supplier-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('supplier-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:supplier_categories,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['code'] = SupplierCategory::makeUniqueCode($validated['name']);
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        SupplierCategory::create($validated);

        return redirect()->route('supplier-categories.index')
            ->with('success', 'Kategori pemasok berhasil ditambahkan');
    }

    public function edit(SupplierCategory $supplierCategory)
    {
        return view('supplier-categories.edit', compact('supplierCategory'));
    }

    public function update(Request $request, SupplierCategory $supplierCategory)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('supplier_categories', 'name')->ignore($supplierCategory->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $supplierCategory, $validated) {
            $oldCode = $supplierCategory->code;
            $newCode = SupplierCategory::makeUniqueCode($validated['name'], $supplierCategory->id);

            $supplierCategory->update([
                'code' => $newCode,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $request->has('is_active'),
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            if ($oldCode !== $newCode) {
                Supplier::where('category', $oldCode)->update(['category' => $newCode]);
            }
        });

        return redirect()->route('supplier-categories.index')
            ->with('success', 'Kategori pemasok berhasil diupdate');
    }

    public function destroy(SupplierCategory $supplierCategory)
    {
        $suppliersCount = $supplierCategory->suppliersCount();

        if ($suppliersCount > 0) {
            return redirect()->route('supplier-categories.index')
                ->with('error', "Kategori tidak dapat dihapus karena digunakan oleh {$suppliersCount} pemasok");
        }

        $supplierCategory->delete();

        return redirect()->route('supplier-categories.index')
            ->with('success', 'Kategori pemasok berhasil dihapus');
    }

    public function toggleStatus(SupplierCategory $supplierCategory)
    {
        $supplierCategory->update(['is_active' => !$supplierCategory->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $supplierCategory->is_active,
            'message' => $supplierCategory->is_active ? 'Kategori pemasok diaktifkan' : 'Kategori pemasok dinonaktifkan',
        ]);
    }
}
