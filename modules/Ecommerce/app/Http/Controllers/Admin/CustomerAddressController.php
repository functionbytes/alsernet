<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerAddress;

class CustomerAddressController extends Controller
{
    public function index(Customer $customer): View
    {
        $addresses = $customer->addresses()->latest()->paginate(20);

        return view('ecommerce::admin.customers.addresses.index', compact('customer', 'addresses'));
    }

    public function create(Customer $customer): View
    {
        return view('ecommerce::admin.customers.addresses.create', compact('customer'));
    }

    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['required', 'string'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
            'type' => ['nullable', 'string', 'max:60'],
        ]);

        if (! empty($validated['is_default'])) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $customer->addresses()->create($validated);

        return redirect()->route('ecommerce.customers.addresses.index', $customer)->with('success', 'Direccion creada.');
    }

    public function edit(Customer $customer, CustomerAddress $address): View
    {
        return view('ecommerce::admin.customers.addresses.edit', compact('customer', 'address'));
    }

    public function update(Request $request, Customer $customer, CustomerAddress $address): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['required', 'string'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
            'type' => ['nullable', 'string', 'max:60'],
        ]);

        if (! empty($validated['is_default'])) {
            $customer->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return redirect()->route('ecommerce.customers.addresses.index', $customer)->with('success', 'Direccion actualizada.');
    }

    public function destroy(Customer $customer, CustomerAddress $address): RedirectResponse
    {
        $address->delete();

        return redirect()->route('ecommerce.customers.addresses.index', $customer)->with('success', 'Direccion eliminada.');
    }
}
