<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Lang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class LocalizationSettingsController extends Controller
{
    /**
     * Display localization backups page
     */
    public function index(): View
    {
        $pageTitle = 'Configuración de localización';
        $breadcrumb = 'Configuración / Localización';

        // Get all available languages
        $languages = Lang::where('available', 1)->get();

        // Get current default language
        $defaultLanguage = Setting::get('default_language', config('app.locale'));

        // Get other localization backups
        $settings = Setting::getLocalizationSettings();

        return view('theme.views.backups.localization.index', compact(
            'pageTitle',
            'breadcrumb',
            'languages',
            'defaultLanguage',
            'settings'
        ));
    }

    /**
     * Update localization backups
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_language' => 'required|string|exists:langs,code',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            'time_format' => 'required|string',
            'currency' => 'required|string',
            'currency_position' => 'required|string|in:before,after',
        ]);

        try {
            Setting::setLocalizationSettings($validated);

            // Update environment variable for default locale
            write_env('APP_LOCALE', $validated['default_language']);
            write_env('APP_TIMEZONE', $validated['timezone']);

            return redirect()->route('manager.backups.localization.index')
                ->with('success', 'Configuración de localización actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Localization settings update failed', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Error al actualizar la configuración.')
                ->withInput();
        }
    }
}
