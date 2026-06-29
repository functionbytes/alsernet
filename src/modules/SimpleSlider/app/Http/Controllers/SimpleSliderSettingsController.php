<?php

namespace Modules\SimpleSlider\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class SimpleSliderSettingsController extends Controller
{
    private const PREFIX = 'simple_slider.';

    public function index(): View
    {
        $get = fn (string $key, mixed $default = '') => Setting::get(self::PREFIX.$key, $default);

        return view('simple_slider::settings.index', compact('get'));
    }

    public function update(Request $request): RedirectResponse
    {
        Setting::set(self::PREFIX.'use_default_config', $request->has('use_default_config') ? '1' : '0');
        Setting::clearPrefixCache(self::PREFIX);

        return redirect()
            ->back()
            ->with('success', 'Ajustes guardados exitosamente.');
    }
}
