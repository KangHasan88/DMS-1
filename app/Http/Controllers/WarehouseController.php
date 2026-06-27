<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::withCount('stockMovements');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%')
                    ->orWhere('address', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $warehouses = $query->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('warehouses.index', [
            'warehouses' => $warehouses,
            'types' => Warehouse::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Warehouse::TYPES))],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = true;
        $validated['is_default'] = $request->boolean('is_default');

        DB::transaction(function () use ($validated) {
            if ($validated['is_default']) {
                Warehouse::query()->update(['is_default' => false]);
            }

            Warehouse::create($validated);
        });

        return back()->with('success', 'Gudang berhasil ditambahkan');
    }

    public function toggleStatus(Warehouse $warehouse)
    {
        if ($warehouse->is_default && $warehouse->is_active) {
            return back()->withErrors(['warehouse' => 'Gudang default tidak bisa dinonaktifkan. Pilih gudang default lain dulu.']);
        }

        $warehouse->update(['is_active' => ! $warehouse->is_active]);

        return back()->with('success', $warehouse->is_active ? 'Gudang diaktifkan' : 'Gudang dinonaktifkan');
    }

    public function setDefault(Warehouse $warehouse)
    {
        if (! $warehouse->is_active) {
            return back()->withErrors(['warehouse' => 'Gudang nonaktif tidak bisa dijadikan default.']);
        }

        DB::transaction(function () use ($warehouse) {
            Warehouse::query()->update(['is_default' => false]);
            $warehouse->update(['is_default' => true]);
        });

        return back()->with('success', 'Gudang default berhasil diperbarui');
    }
}
