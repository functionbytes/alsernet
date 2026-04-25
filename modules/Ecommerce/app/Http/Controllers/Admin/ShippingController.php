<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Shipping;

class ShippingController extends Controller
{
    public function index(): View
    {
        $shippings = Shipping::query()->with('rules')->paginate(20);

        return view('ecommerce::admin.shipping.index', compact('shippings'));
    }

    public function create(): View
    {
        return view('ecommerce::admin.shipping.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        Shipping::query()->create($validated);

        return redirect()->route('ecommerce.shipping.index')->with('success', 'Metodo de envio creado.');
    }

    public function edit(Shipping $shipping): View
    {
        return view('ecommerce::admin.shipping.edit', compact('shipping'));
    }

    public function update(Request $request, Shipping $shipping): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        $shipping->update($validated);

        return redirect()->route('ecommerce.shipping.index')->with('success', 'Metodo de envio actualizado.');
    }

    public function destroy(Shipping $shipping): RedirectResponse
    {
        $shipping->delete();

        return redirect()->route('ecommerce.shipping.index')->with('success', 'Metodo de envio eliminado.');
    }
}
