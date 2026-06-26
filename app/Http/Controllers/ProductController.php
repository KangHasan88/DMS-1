<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPriceHistory;
use App\Models\ProductPrincipal;
use App\Models\ReturnablePackage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        $query = Product::query()->with(['principal', 'unit']);
        
        // Search
        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('category', 'like', '%' . $request->search . '%')
                    ->orWhereHas('principal', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('code', 'like', '%' . $request->search . '%');
                    });
            });
        }
        
        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('principal_id')) {
            $query->where('principal_id', $request->principal_id);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        // Per page
        $perPage = $request->get('per_page', 10);
        
        $products = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        // Get categories for filter
        $categories = ProductCategory::active()->orderBy('sort_order')->orderBy('name')->pluck('name');
        $principals = ProductPrincipal::active()->orderBy('sort_order')->orderBy('name')->get();
        
        return view('products.index', compact('products', 'categories', 'principals'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $categories = ProductCategory::active()->orderBy('sort_order')->orderBy('name')->get();
        $principals = ProductPrincipal::active()->orderBy('sort_order')->orderBy('name')->get();
        $returnablePackages = ReturnablePackage::active()->orderBy('name')->get();
        $packagingFlows = Product::PACKAGING_FLOW_LIST;

        return view('products.create', compact('categories', 'principals', 'returnablePackages', 'packagingFlows'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => ['nullable', 'string', 'max:100', $this->activeProductCategoryRule()],
            'principal_id' => ['nullable', 'exists:product_principals,id'],
            'unit_id' => 'required|exists:units,id',
            'returnable_package_id' => 'nullable|exists:returnable_packages,id',
            'returnable_package_quantity_per_unit' => 'nullable|integer|min:0',
            'returnable_package_default_flow' => ['nullable', Rule::in(array_keys(Product::PACKAGING_FLOW_LIST))],
            'price' => 'required|numeric|min:0',
            'base_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'boolean',
        ]);
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }
        
        // Set default values
        $validated['is_active'] = $request->has('is_active');
        $validated = $this->normalizeReturnablePackaging($validated);
        
        $product = Product::create($validated);
        
        // Catat history harga awal (tanpa old price)
        $product->priceHistories()->create([
            'user_id' => Auth::id(),
            'old_price' => null,
            'new_price' => $product->price,
            'old_base_price' => null,
            'new_base_price' => $product->base_price,
            'reason' => 'Produk baru ditambahkan',
        ]);
        
        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $categories = ProductCategory::active()->orderBy('sort_order')->orderBy('name')->get();
        $principals = ProductPrincipal::query()
            ->where(function ($query) use ($product) {
                $query->where('is_active', true)
                    ->when($product->principal_id, fn ($query) => $query->orWhere('id', $product->principal_id));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $returnablePackages = ReturnablePackage::active()->orderBy('name')->get();
        $packagingFlows = Product::PACKAGING_FLOW_LIST;

        return view('products.edit', compact('product', 'categories', 'principals', 'returnablePackages', 'packagingFlows'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => ['nullable', 'string', 'max:100', $this->activeProductCategoryRule($product->category)],
            'principal_id' => ['nullable', 'exists:product_principals,id'],
            'unit_id' => 'required|exists:units,id',
            'returnable_package_id' => 'nullable|exists:returnable_packages,id',
            'returnable_package_quantity_per_unit' => 'nullable|integer|min:0',
            'returnable_package_default_flow' => ['nullable', Rule::in(array_keys(Product::PACKAGING_FLOW_LIST))],
            'price' => 'required|numeric|min:0',
            'base_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'boolean',
            'price_change_reason' => 'nullable|string|max:500',
        ]);
        
        // Simpan data lama sebelum update
        $oldData = [
            'price' => $product->price,
            'base_price' => $product->base_price,
        ];
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = $imagePath;
        }
        
        $validated['is_active'] = $request->has('is_active');
        $validated = $this->normalizeReturnablePackaging($validated);
        
        $product->update($validated);
        
        // Data baru setelah update
        $newData = [
            'price' => $product->price,
            'base_price' => $product->base_price,
        ];
        
        // Catat perubahan harga jika ada perubahan
        $priceChanged = $oldData['price'] != $newData['price'];
        $basePriceChanged = $oldData['base_price'] != $newData['base_price'];
        
        if ($priceChanged || $basePriceChanged) {
            $reason = $request->input('price_change_reason', 'Update produk via admin panel');
            
            $product->priceHistories()->create([
                'user_id' => Auth::id(),
                'old_price' => $oldData['price'],
                'new_price' => $newData['price'],
                'old_base_price' => $oldData['base_price'],
                'new_base_price' => $newData['base_price'],
                'reason' => $reason,
            ]);
        }
        
        $message = 'Produk berhasil diupdate';
        if ($priceChanged) {
            $message .= ' (Harga diubah dari Rp ' . number_format($oldData['price'], 0, ',', '.') . 
                        ' menjadi Rp ' . number_format($newData['price'], 0, ',', '.') . ')';
        }
        
        return redirect()->route('products.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists() || $product->stockMovements()->exists()) {
            return back()->with('error', 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi. Nonaktifkan produk jika tidak dipakai lagi.');
        }

        // Delete image if exists
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        
        $product->delete();
        
        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil dihapus');
    }
    
    /**
     * Toggle product status.
     */
    public function toggleStatus(Product $product)
    {
        $oldStatus = $product->is_active;
        $product->update(['is_active' => !$oldStatus]);
        
        return response()->json([
            'success' => true,
            'is_active' => $product->is_active,
            'message' => $product->is_active ? 'Produk diaktifkan' : 'Produk dinonaktifkan'
        ]);
    }
    
    /**
     * Display price history for a product.
     */
    public function priceHistory(Product $product)
    {
        $priceHistories = $product->priceHistories()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('products.price-history', compact('product', 'priceHistories'));
    }

    private function normalizeReturnablePackaging(array $validated): array
    {
        $packageId = $validated['returnable_package_id'] ?? null;
        $quantityPerUnit = (int) ($validated['returnable_package_quantity_per_unit'] ?? 0);
        $defaultFlow = $validated['returnable_package_default_flow'] ?? null;

        if (!$packageId) {
            $validated['returnable_package_id'] = null;
            $validated['returnable_package_quantity_per_unit'] = 0;
            $validated['returnable_package_default_flow'] = null;

            return $validated;
        }

        if ($quantityPerUnit < 1) {
            $quantityPerUnit = 1;
        }

        $validated['returnable_package_quantity_per_unit'] = $quantityPerUnit;
        $validated['returnable_package_default_flow'] = $defaultFlow ?: Product::PACKAGING_FLOW_RETURNABLE;

        return $validated;
    }

    private function activeProductCategoryRule(?string $currentCategory = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($currentCategory) {
            if ($value === null || $value === '') {
                return;
            }

            $isActiveMaster = ProductCategory::where('name', $value)
                ->where('is_active', true)
                ->exists();

            if ($isActiveMaster || ($currentCategory && $value === $currentCategory)) {
                return;
            }

            $fail('Kategori produk harus dipilih dari master kategori aktif.');
        };
    }
}
