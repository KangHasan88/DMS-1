<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAddressController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'type' => 'required|in:invoice,shipping,both',
            'address' => 'required|string',
            'recipient_name' => 'nullable|string|max:150',
            'recipient_phone' => 'nullable|string|max:30',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'is_default_invoice' => 'boolean',
            'is_default_shipping' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($customer, &$validated) {
            $validated['is_default_invoice'] = (bool) ($validated['is_default_invoice'] ?? false);
            $validated['is_default_shipping'] = (bool) ($validated['is_default_shipping'] ?? false);
            $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

            if (!in_array($validated['type'], [CustomerAddress::TYPE_INVOICE, CustomerAddress::TYPE_BOTH], true)) {
                $validated['is_default_invoice'] = false;
            }

            if (!in_array($validated['type'], [CustomerAddress::TYPE_SHIPPING, CustomerAddress::TYPE_BOTH], true)) {
                $validated['is_default_shipping'] = false;
            }

            if ($validated['is_default_invoice']) {
                $customer->addresses()->update(['is_default_invoice' => false]);
            }

            if ($validated['is_default_shipping']) {
                $customer->addresses()->update(['is_default_shipping' => false]);
            }

            $customer->addresses()->create($validated);
        });

        return back()->with('success', 'Alamat pelanggan berhasil ditambahkan');
    }

    public function update(Request $request, Customer $customer, CustomerAddress $address)
    {
        abort_unless($address->customer_id === $customer->id, 404);

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'type' => 'required|in:invoice,shipping,both',
            'address' => 'required|string',
            'recipient_name' => 'nullable|string|max:150',
            'recipient_phone' => 'nullable|string|max:30',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'is_default_invoice' => 'boolean',
            'is_default_shipping' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($customer, $address, &$validated) {
            $validated['is_default_invoice'] = (bool) ($validated['is_default_invoice'] ?? false);
            $validated['is_default_shipping'] = (bool) ($validated['is_default_shipping'] ?? false);
            $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

            if (!in_array($validated['type'], [CustomerAddress::TYPE_INVOICE, CustomerAddress::TYPE_BOTH], true)) {
                $validated['is_default_invoice'] = false;
            }

            if (!in_array($validated['type'], [CustomerAddress::TYPE_SHIPPING, CustomerAddress::TYPE_BOTH], true)) {
                $validated['is_default_shipping'] = false;
            }

            if ($validated['is_default_invoice']) {
                $customer->addresses()->whereKeyNot($address->id)->update(['is_default_invoice' => false]);
            }

            if ($validated['is_default_shipping']) {
                $customer->addresses()->whereKeyNot($address->id)->update(['is_default_shipping' => false]);
            }

            $address->update($validated);
        });

        return back()->with('success', 'Alamat pelanggan berhasil diperbarui');
    }

    public function destroy(Customer $customer, CustomerAddress $address)
    {
        abort_unless($address->customer_id === $customer->id, 404);

        $address->delete();

        return back()->with('success', 'Alamat pelanggan berhasil dihapus');
    }
}
