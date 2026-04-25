<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreCustomerRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateCustomerRequest;
use Modules\Ecommerce\Models\Customer;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::query()->latest()->paginate(20);

        return view('ecommerce::admin.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('ecommerce::admin.customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = bcrypt($data['password']);
        Customer::query()->create($data);

        return redirect()->route('ecommerce.customers.index')->with('success', 'Cliente creado exitosamente.');
    }

    public function edit(Customer $customer): View
    {
        return view('ecommerce::admin.customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        $customer->update($data);

        return redirect()->route('ecommerce.customers.index')->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('ecommerce.customers.index')->with('success', 'Cliente eliminado exitosamente.');
    }
}
