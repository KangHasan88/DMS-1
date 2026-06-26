<?php

namespace App\Http\Controllers;

use App\Models\ProductPrincipal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductPrincipalController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductPrincipal::withCount('products');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $principals = $query->orderBy('sort_order')->orderBy('name')->paginate(20);

        return view('product-principals.index', compact('principals'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:product_principals,code'],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        ProductPrincipal::create($validated);

        return back()->with('success', 'Principal berhasil ditambahkan');
    }

    public function update(Request $request, ProductPrincipal $productPrincipal)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('product_principals', 'code')->ignore($productPrincipal)],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $productPrincipal->update($validated);

        return back()->with('success', 'Principal berhasil diupdate');
    }

    public function toggleStatus(ProductPrincipal $productPrincipal)
    {
        $productPrincipal->update(['is_active' => ! $productPrincipal->is_active]);

        return back()->with('success', $productPrincipal->is_active ? 'Principal diaktifkan' : 'Principal dinonaktifkan');
    }
}
