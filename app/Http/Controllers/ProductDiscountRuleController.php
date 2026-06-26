<?php

namespace App\Http\Controllers;

use App\Models\CompanyBranch;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductDiscountRule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        $customers = Customer::with('companyBranch')->where('is_active', true)->orderBy('name')->get();
        $customerTypes = CustomerType::where('is_active', true)->orderBy('name')->get();
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();

        return view('product_discount_rules.index', compact('rules', 'products', 'customers', 'customerTypes', 'companyBranches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['exists:customers,id'],
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

        $customerIds = collect($validated['customer_ids'] ?? [])->filter()->unique()->values();
        unset($validated['customer_ids']);
        $validated['is_active'] = true;

        if ($customerIds->isNotEmpty()) {
            $validated['customer_type'] = null;
            $this->ensureCustomersBelongToBranch($validated['company_branch_id'] ?? null, $customerIds->all());
            $this->ensureNoOverlappingDiscountRules($validated, $customerIds->all());

            $customerIds->each(function ($customerId) use ($validated) {
                ProductDiscountRule::create($validated + ['customer_id' => $customerId]);
            });

            return back()->with('success', $customerIds->count() . ' aturan diskon customer berhasil ditambahkan.');
        }

        $this->ensureNoOverlappingDiscountRules($validated, [null]);

        ProductDiscountRule::create($validated + ['customer_id' => null]);

        return back()->with('success', 'Aturan diskon berhasil ditambahkan.');
    }

    public function toggleStatus(ProductDiscountRule $productDiscountRule)
    {
        $productDiscountRule->update(['is_active' => !$productDiscountRule->is_active]);

        return back()->with('success', 'Status aturan diskon berhasil diperbarui.');
    }

    private function ensureNoOverlappingDiscountRules(array $data, array $customerIds): void
    {
        foreach ($customerIds as $customerId) {
            $exists = ProductDiscountRule::query()
                ->where('is_active', true)
                ->tap(fn ($query) => $this->whereNullableValue($query, 'product_id', $data['product_id'] ?? null))
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
                    'starts_at' => 'Periode aturan diskon bentrok dengan aturan aktif pada scope dan minimum qty yang sama.',
                ]);
            }
        }
    }

    private function ensureCustomersBelongToBranch(mixed $companyBranchId, array $customerIds): void
    {
        if (!filled($companyBranchId) || empty($customerIds)) {
            return;
        }

        $invalidCustomers = Customer::query()
            ->whereIn('id', $customerIds)
            ->where('company_branch_id', '!=', $companyBranchId)
            ->exists();

        if ($invalidCustomers) {
            throw ValidationException::withMessages([
                'customer_ids' => 'Customer khusus harus sesuai dengan cabang yang dipilih.',
            ]);
        }
    }

    private function whereNullableValue($query, string $column, mixed $value)
    {
        return filled($value) ? $query->where($column, $value) : $query->whereNull($column);
    }
}
