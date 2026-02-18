<?php

namespace Modules\Cookie\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;

class CookieSettingsController
{
    private const PREFIX = 'cookie.';

    public function index(): View
    {
        $get = fn (string $key, string $default = '') => Setting::get(self::PREFIX.$key, $default);

        return view('cookie::settings.index', [
            'get' => $get,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled' => 'nullable|string',
            'style' => 'nullable|in:full-width,minimal',
            'message' => 'nullable|string',
            'accept_btn_text' => 'nullable|string',
            'reject_btn_text' => 'nullable|string',
            'customize_btn_text' => 'nullable|string',
            'save_btn_text' => 'nullable|string',
            'more_info_text' => 'nullable|string',
            'more_info_url' => 'nullable|url',
            'bg_color' => 'nullable|string',
            'text_color' => 'nullable|string',
            'btn_color' => 'nullable|string',
            'max_width' => 'nullable|numeric',
            'position' => 'nullable|string',
            'google_analytics_enabled' => 'nullable|string',
            'google_analytics_id' => 'nullable|string',
            'facebook_pixel_enabled' => 'nullable|string',
            'facebook_pixel_id' => 'nullable|string',
        ]);

        $data = $request->except(['_token', '_method']);

        // Checkbox fields: persist '0' when not submitted
        foreach (['enabled', 'google_analytics_enabled', 'facebook_pixel_enabled'] as $checkbox) {
            $data[$checkbox] = $request->has($checkbox) ? '1' : '0';
        }

        foreach ($data as $key => $value) {
            Setting::set(self::PREFIX.$key, $value ?? '');
        }

        // Clear cache
        Setting::clearPrefixCache('cookie.');

        return redirect()
            ->back()
            ->with('success', 'Configuración de cookies actualizada correctamente.');
    }
}
