<?php

namespace App\Http\Controllers;

use App\Models\CompanyBranch;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductBonusRule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductBonusRuleController extends Controller
{
    public function index(Request $request)
    {
        $rules = ProductBonusRule::with(['triggerProduct', 'bonusProduct', 'customer', 'companyBranch'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->whereHas('triggerProduct', fn ($product) => $product->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('bonusProduct', fn ($product) => $product->where('name', 'like', "%{$search}%"))
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

        return view('product_bonus_rules.index', compact('rules', 'products', 'customers', 'customerTypes', 'companyBranches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'trigger_product_id' => ['required', 'exists:products,id'],
            'bonus_product_id' => ['required', 'exists:products,id', 'different:trigger_product_id'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['exists:customers,id'],
            'customer_type' => ['nullable', 'exists:customer_types,code'],
            'company_branch_id' => ['nullable', 'exists:company_branches,id'],
            'min_quantity' => ['required', 'integer', 'min:1'],
            'bonus_quantity' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $customerIds = collect($validated['customer_ids'] ?? [])->filter()->unique()->values();
        unset($validated['customer_ids']);
        $validated['is_active'] = true;

        if ($customerIds->isNotEmpty()) {
            $validated['customer_type'] = null;
            $this->ensureNoOverlappingBonusRules($validated, $customerIds->all());

            $customerIds->each(function ($customerId) use ($validated) {
                ProductBonusRule::create($validated + ['customer_id' => $customerId]);
            });

            return back()->with('success', $customerIds->count() . ' aturan bonus customer berhasil ditambahkan.');
        }

        $this->ensureNoOverlappingBonusRules($validated, [null]);

        ProductBonusRule::create($validated + ['customer_id' => null]);

        return back()->with('success', 'Aturan bonus berhasil ditambahkan.');
    }

    public function toggleStatus(ProductBonusRule $productBonusRule)
    {
        $productBonusRule->update(['is_active' => !$productBonusRule->is_active]);

        return back()->with('success', 'Status aturan bonus berhasil diperbarui.');
    }

    private function ensureNoOverlappingBonusRules(array $data, array $customerIds): void
    {
        foreach ($customerIds as $customerId) {
            $exists = ProductBonusRule::query()
                ->where('is_active', true)
                ->where('trigger_product_id', $data['trigger_product_id'])
                ->when($customerId, fn ($query) => $query->where('customer_id', $customerId), fn ($query) => $query->whereNull('customer_id'))
                ->when(
                    $customerId,
                    fn ($query) => $query->whereNull('customer_type'),
                    fn ($query) => $this->whereNullableValue($query, 'customer_type', $data['customer_type'] ?? null)
                )
                ->tap(fn ($query) => $this->whereNullableValue($query, 'company_branch_id', $data['company_branch_id'] ?? null))
                ->where('min_quantity', (int) $data['min_quantity'])
                ->whereDate('starts_at', '<=', $data['ends_at'] ?? '9999-12-31')
                ->where(function ($query) use ($data) {
                    $query->whereNull('ends_at')
                        ->orWhereDate('ends_at', '>=', $data['starts_at']);
                })
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Periode aturan bonus bentrok dengan aturan aktif pada scope dan minimum qty yang sama.',
                ]);
            }
        }
    }

    private function whereNullableValue($query, string $column, mixed $value)
    {
        return filled($value) ? $query->where($column, $value) : $query->whereNull($column);
    }
}
