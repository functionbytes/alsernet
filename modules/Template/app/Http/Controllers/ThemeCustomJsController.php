<?php

namespace Modules\Template\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;

class ThemeCustomJsController
{
    /**
     * Mostrar formulario de configuración de JavaScript personalizado
     */
    public function index(): View
    {
        $headerJs = Setting::get('theme.custom_header_js', '');
        $footerJs = Setting::get('theme.custom_footer_js', '');

        return view('template::settings.custom-js', compact('headerJs', 'footerJs'));
    }

    /**
     * Guardar cambios de JavaScript personalizado
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'header_js' => 'nullable|string',
            'footer_js' => 'nullable|string',
        ]);

        Setting::set('theme.custom_header_js', $request->input('header_js', ''));
        Setting::set('theme.custom_footer_js', $request->input('footer_js', ''));

        return redirect()
            ->back()
            ->with('success', __('template::template.custom_js_updated'));
    }
}
