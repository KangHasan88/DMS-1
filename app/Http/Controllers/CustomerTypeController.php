<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerType::query()->search($request->search);

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $perPage = $request->get('per_page', 10);
        $types = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);

        return view('customer-types.index', compact('types'));
    }

    public function create()
    {
        return view('customer-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:customer_types,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['code'] = CustomerType::makeUniqueCode($validated['name']);
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        CustomerType::create($validated);

        return redirect()->to($request->input('redirect_to', route('customer-types.index')))
            ->with('success', 'Tipe pelanggan berhasil ditambahkan');
    }

    public function edit(CustomerType $customerType)
    {
        return view('customer-types.edit', compact('customerType'));
    }

    public function update(Request $request, CustomerType $customerType)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('customer_types', 'name')->ignore($customerType->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $customerType, $validated) {
            $oldCode = $customerType->code;

            $customerType->update([
                'name' => $validated['name'],
                'code' => CustomerType::makeUniqueCode($validated['name'], $customerType->id),
                'description' => $validated['description'] ?? null,
                'is_active' => $request->has('is_active'),
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            if ($oldCode !== $customerType->code) {
                Customer::where('customer_type', $oldCode)->update(['customer_type' => $customerType->code]);
            }
        });

        return redirect()->route('customer-types.index')
            ->with('success', 'Tipe pelanggan berhasil diupdate');
    }

    public function destroy(CustomerType $customerType)
    {
        $customersCount = $customerType->customersCount();

        if ($customersCount > 0) {
            return redirect()->route('customer-types.index')
                ->with('error', "Tipe tidak dapat dihapus karena digunakan oleh {$customersCount} pelanggan");
        }

        $customerType->delete();

        return redirect()->route('customer-types.index')
            ->with('success', 'Tipe pelanggan berhasil dihapus');
    }

    public function toggleStatus(CustomerType $customerType)
    {
        $customerType->update(['is_active' => !$customerType->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $customerType->is_active,
            'message' => $customerType->is_active ? 'Tipe pelanggan diaktifkan' : 'Tipe pelanggan dinonaktifkan',
        ]);
    }
}
