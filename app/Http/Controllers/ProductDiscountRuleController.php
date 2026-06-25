<?php

namespace App\Http\Controllers;

use App\Models\CompanyBranch;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductDiscountRule;
use Illuminate\Http\Request;

class ProductDiscountRuleController extends Controller
{
    public function index(Request $request)
    {
        $rules = ProductDiscountRule::with(['product', 'customer', 'companyBranch'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->whereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhere('customer_type', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
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

        return view('product_discount_rules.index', compact('rules', 'products', 'customers', 'customerTypes', 'companyBranches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_type' => ['nullable', 'exists:customer_types,code'],
            'company_branch_id' => ['nullable', 'exists:company_branches,id'],
            'discount_type' => ['required', 'in:percent,nominal'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'min_quantity' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['discount_type'] === ProductDiscountRule::TYPE_PERCENT) {
            $request->validate(['discount_value' => ['numeric', 'max:100']]);
        }

        if (!empty($validated['customer_id'])) {
            $validated['customer_type'] = null;
        }

        $validated['is_active'] = true;

        ProductDiscountRule::create($validated);

        return back()->with('success', 'Aturan diskon berhasil ditambahkan.');
    }

    public function toggleStatus(ProductDiscountRule $productDiscountRule)
    {
        $productDiscountRule->update(['is_active' => !$productDiscountRule->is_active]);

        return back()->with('success', 'Status aturan diskon berhasil diperbarui.');
    }
}
