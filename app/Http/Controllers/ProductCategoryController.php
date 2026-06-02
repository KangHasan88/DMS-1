<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductCategory::query()->search($request->search);

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $perPage = $request->get('per_page', 10);
        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);

        return view('product-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('product-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:product_categories,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = ProductCategory::makeUniqueSlug($validated['name']);
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ProductCategory::create($validated);

        return redirect()->to($request->input('redirect_to', route('product-categories.index')))
            ->with('success', 'Kategori produk berhasil ditambahkan');
    }

    public function edit(ProductCategory $productCategory)
    {
        return view('product-categories.edit', compact('productCategory'));
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_categories', 'name')->ignore($productCategory->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $productCategory, $validated) {
            $oldName = $productCategory->name;

            $productCategory->update([
                'name' => $validated['name'],
                'slug' => ProductCategory::makeUniqueSlug($validated['name'], $productCategory->id),
                'description' => $validated['description'] ?? null,
                'is_active' => $request->has('is_active'),
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            if ($oldName !== $productCategory->name) {
                Product::where('category', $oldName)->update(['category' => $productCategory->name]);
            }
        });

        return redirect()->route('product-categories.index')
            ->with('success', 'Kategori produk berhasil diupdate');
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productsCount = $productCategory->productsCount();

        if ($productsCount > 0) {
            return redirect()->route('product-categories.index')
                ->with('error', "Kategori tidak dapat dihapus karena digunakan oleh {$productsCount} produk");
        }

        $productCategory->delete();

        return redirect()->route('product-categories.index')
            ->with('success', 'Kategori produk berhasil dihapus');
    }

    public function toggleStatus(ProductCategory $productCategory)
    {
        $productCategory->update(['is_active' => !$productCategory->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $productCategory->is_active,
            'message' => $productCategory->is_active ? 'Kategori produk diaktifkan' : 'Kategori produk dinonaktifkan',
        ]);
    }
}
