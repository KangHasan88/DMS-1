<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UnitCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = UnitCategory::query()->search($request->search);

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $perPage = $request->get('per_page', 10);
        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);

        return view('unit-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('unit-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:unit_categories,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = UnitCategory::makeUniqueSlug($validated['name']);
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        UnitCategory::create($validated);

        return redirect()->to($request->input('redirect_to', route('unit-categories.index')))
            ->with('success', 'Kategori satuan berhasil ditambahkan');
    }

    public function edit(UnitCategory $unitCategory)
    {
        return view('unit-categories.edit', compact('unitCategory'));
    }

    public function update(Request $request, UnitCategory $unitCategory)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('unit_categories', 'name')->ignore($unitCategory->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $unitCategory, $validated) {
            $oldName = $unitCategory->name;

            $unitCategory->update([
                'name' => $validated['name'],
                'slug' => UnitCategory::makeUniqueSlug($validated['name'], $unitCategory->id),
                'description' => $validated['description'] ?? null,
                'is_active' => $request->has('is_active'),
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            if ($oldName !== $unitCategory->name) {
                Unit::where('category', $oldName)->update(['category' => $unitCategory->name]);
            }
        });

        return redirect()->route('unit-categories.index')
            ->with('success', 'Kategori satuan berhasil diupdate');
    }

    public function destroy(UnitCategory $unitCategory)
    {
        $unitsCount = $unitCategory->unitsCount();

        if ($unitsCount > 0) {
            return redirect()->route('unit-categories.index')
                ->with('error', "Kategori tidak dapat dihapus karena digunakan oleh {$unitsCount} satuan");
        }

        $unitCategory->delete();

        return redirect()->route('unit-categories.index')
            ->with('success', 'Kategori satuan berhasil dihapus');
    }

    public function toggleStatus(UnitCategory $unitCategory)
    {
        $unitCategory->update(['is_active' => !$unitCategory->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $unitCategory->is_active,
            'message' => $unitCategory->is_active ? 'Kategori satuan diaktifkan' : 'Kategori satuan dinonaktifkan',
        ]);
    }
}
