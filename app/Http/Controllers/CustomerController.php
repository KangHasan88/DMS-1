<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $query = Customer::with('user');
        
        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
        }
        
        // Filter by type
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        $perPage = $request->get('per_page', 10);
        $customers = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        // Get customer types for filter
        $customerTypes = ['regular', 'premium', 'wholesale'];
        
        return view('customers.index', compact('customers', 'customerTypes'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email', 'unique:users,email'],
            'address' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'customer_type' => 'required|in:regular,premium,wholesale',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Create user account for customer
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? $validated['phone'] . '@customer.temp',
                'phone' => $validated['phone'],
                'password' => 'password123',
                'is_active' => $validated['is_active'] ?? true,
            ]);
            $user->assignRole('customer');
            
            // Create customer profile
            $validated['user_id'] = $user->id;
            $validated['is_active'] = $validated['is_active'] ?? true;
            
            Customer::create($validated);
            
            DB::commit();
            
            return redirect()->route('customers.index')
                ->with('success', 'Customer berhasil ditambahkan');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambahkan customer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        $customer->load('user', 'orders');
        $totalOrders = $customer->orders()->count();
        $totalSpent = $customer->orders()->where('status', 'delivered')->sum('total');
        $lastOrder = $customer->orders()->latest()->first();
        
        return view('customers.show', compact('customer', 'totalOrders', 'totalSpent', 'lastOrder'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'phone')->ignore($customer->id),
                Rule::unique('users', 'phone')->ignore($customer->user_id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
                Rule::unique('users', 'email')->ignore($customer->user_id),
            ],
            'address' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'customer_type' => 'required|in:regular,premium,wholesale',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Update user account
            if ($customer->user) {
                $customer->user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?? $customer->user->email,
                    'phone' => $validated['phone'],
                    'is_active' => $validated['is_active'] ?? true,
                ]);
            }
            
            // Update customer profile
            $validated['is_active'] = $validated['is_active'] ?? true;
            $customer->update($validated);
            
            DB::commit();
            
            return redirect()->route('customers.index')
                ->with('success', 'Customer berhasil diupdate');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate customer: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        // Check if customer has orders
        if ($customer->orders()->count() > 0) {
            return back()->with('error', 'Customer tidak dapat dihapus karena memiliki riwayat order');
        }
        
        DB::beginTransaction();
        
        try {
            // Delete user account
            if ($customer->user) {
                $customer->user->delete();
            }
            
            // Delete customer profile
            $customer->delete();
            
            DB::commit();
            
            return redirect()->route('customers.index')
                ->with('success', 'Customer berhasil dihapus');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus customer: ' . $e->getMessage());
        }
    }
    
    /**
     * Toggle customer status.
     */
    public function toggleStatus(Customer $customer)
    {
        $newStatus = !$customer->is_active;
        $customer->update(['is_active' => $newStatus]);
        
        // Update user status
        if ($customer->user) {
            $customer->user->update(['is_active' => $newStatus]);
        }
        
        return response()->json([
            'success' => true,
            'is_active' => $newStatus,
            'message' => $newStatus ? 'Customer diaktifkan' : 'Customer dinonaktifkan'
        ]);
    }
    
    /**
     * Top up customer wallet.
     */
    public function topupWallet(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000',
            'notes' => 'nullable|string',
        ]);
        
        if (!$customer->user) {
            return back()->with('error', 'Customer tidak memiliki akun user');
        }
        
        DB::transaction(function () use ($customer, $validated) {
            $wallet = $customer->user->initWallet();
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $wallet->addBalance($validated['amount'], null, $validated['notes'] ?? 'Topup via admin');
        });
        
        return redirect()->route('customers.show', $customer)
            ->with('success', 'Topup Rp ' . number_format($validated['amount'], 0, ',', '.') . ' berhasil');
    }
    
    /**
     * Get customer order history.
     */
    public function orderHistory(Customer $customer)
    {
        $orders = $customer->orders()->with('items')->orderBy('created_at', 'desc')->paginate(10);
        
        return view('customers.order-history', compact('customer', 'orders'));
    }
}
