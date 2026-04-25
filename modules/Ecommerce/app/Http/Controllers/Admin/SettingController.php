<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class SettingController extends Controller
{
    private array $keys = [
        'ecommerce.currency',
        'ecommerce.currency_symbol',
        'ecommerce.products_per_page',
        'ecommerce.allow_guest_checkout',
    ];

    public function index(): View
    {
        $settings = [];
        foreach ($this->keys as $key) {
            $settings[str_replace('ecommerce.', '', $key)] = Setting::get($key);
        }

        return view('ecommerce::admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'products_per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'allow_guest_checkout' => ['nullable', 'boolean'],
        ]);

        Setting::set('ecommerce.currency', $validated['currency']);
        Setting::set('ecommerce.currency_symbol', $validated['currency_symbol']);
        Setting::set('ecommerce.products_per_page', $validated['products_per_page']);
        Setting::set('ecommerce.allow_guest_checkout', $request->boolean('allow_guest_checkout') ? '1' : '0');

        return redirect()->route('ecommerce.settings.index')->with('success', 'Configuracion actualizada.');
    }
}
