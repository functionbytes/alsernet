<?php

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;

class ThemeCustomHtmlController extends Controller
{
    /**
     * Mostrar formulario de configuración de HTML personalizado
     */
    public function index(): View
    {
        $this->authorize('template.custom-code');

        $headerHtml = Setting::get('theme.custom_header_html', '');
        $footerHtml = Setting::get('theme.custom_footer_html', '');

        return view('template::settings.custom-html', compact('headerHtml', 'footerHtml'));
    }

    /**
     * Guardar cambios de HTML personalizado
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('template.custom-code');

        $request->validate([
            'header_html' => 'nullable|string',
            'footer_html' => 'nullable|string',
        ]);

        Setting::set('theme.custom_header_html', $request->input('header_html', ''));
        Setting::set('theme.custom_footer_html', $request->input('footer_html', ''));

        return redirect()
            ->back()
            ->with('success', __('template::template.custom_html_updated'));
    }
}
