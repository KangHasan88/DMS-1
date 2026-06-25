<?php

namespace App\Http\Controllers;

use App\Models\CompanyBranch;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPriceRule;
use Illuminate\Http\Request;

class ProductPriceRuleController extends Controller
{
    public function index(Request $request)
    {
        $rules = ProductPriceRule::with(['product', 'customer', 'companyBranch'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->whereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhere('customer_type', 'like', "%{$search}%");
                });
            })
            ->latest('is_active')
            ->latest('starts_at')
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        $products = Product::active()->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $customerTypes = CustomerType::where('is_active', true)->orderBy('name')->get();
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();

        return view('product_price_rules.index', compact('rules', 'products', 'customers', 'customerTypes', 'companyBranches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['exists:customers,id'],
            'customer_type' => ['nullable', 'exists:customer_types,code'],
            'company_branch_id' => ['nullable', 'exists:company_branches,id'],
            'price' => ['required', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $customerIds = collect($validated['customer_ids'] ?? [])->filter()->unique()->values();
        unset($validated['customer_ids']);
        $validated['is_active'] = true;

        if ($customerIds->isNotEmpty()) {
            $validated['customer_type'] = null;

            $customerIds->each(function ($customerId) use ($validated) {
                ProductPriceRule::create($validated + ['customer_id' => $customerId]);
            });

            return back()->with('success', $customerIds->count() . ' aturan harga customer berhasil ditambahkan.');
        }

        ProductPriceRule::create($validated + ['customer_id' => null]);

        return back()->with('success', 'Aturan harga berhasil ditambahkan.');
    }

    public function toggleStatus(ProductPriceRule $productPriceRule)
    {
        $productPriceRule->update(['is_active' => !$productPriceRule->is_active]);

        return back()->with('success', 'Status aturan harga berhasil diperbarui.');
    }
}
