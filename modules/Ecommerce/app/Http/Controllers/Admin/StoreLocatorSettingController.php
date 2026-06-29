<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\StoreLocator;

class StoreLocatorSettingController extends Controller
{
    public function index(): View
    {
        $storeLocators = StoreLocator::query()->orderByDesc('is_primary')->get();

        return view('ecommerce::settings.store-locators.index', compact('storeLocators'));
    }

    public function create(): View
    {
        return view('ecommerce::settings.store-locators.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:60'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'country' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'is_primary' => ['boolean'],
            'is_shipping_location' => ['boolean'],
        ]);

        $validated['is_primary'] = $request->has('is_primary');
        $validated['is_shipping_location'] = $request->has('is_shipping_location');

        StoreLocator::query()->create($validated);

        return redirect()->route('settings.ecommerce.store-locators.index')->with('success', 'Tienda creada.');
    }

    public function edit(StoreLocator $storeLocator): View
    {
        return view('ecommerce::settings.store-locators.edit', compact('storeLocator'));
    }

    public function update(Request $request, StoreLocator $storeLocator): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:60'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'country' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'is_primary' => ['boolean'],
            'is_shipping_location' => ['boolean'],
        ]);

        $validated['is_primary'] = $request->has('is_primary');
        $validated['is_shipping_location'] = $request->has('is_shipping_location');

        $storeLocator->update($validated);

        return redirect()->route('settings.ecommerce.store-locators.index')->with('success', 'Tienda actualizada.');
    }

    public function destroy(StoreLocator $storeLocator): RedirectResponse
    {
        $storeLocator->delete();

        return redirect()->route('settings.ecommerce.store-locators.index')->with('success', 'Tienda eliminada.');
    }
}
