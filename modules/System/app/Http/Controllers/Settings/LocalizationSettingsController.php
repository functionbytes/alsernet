<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Lang;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;

class LocalizationSettingsController extends Controller
{
    /**
     * Display localization backups page
     */
    public function index()
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
    public function update(Request $request)
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
            $this->updateEnvVariable('APP_LOCALE', $validated['default_language']);
            $this->updateEnvVariable('APP_TIMEZONE', $validated['timezone']);

            return redirect()->route('manager.backups.localization.index')
                ->with('success', 'Configuración de localización actualizada correctamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar la configuración: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update environment variable in .env file
     */
    private function updateEnvVariable($key, $value)
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            return false;
        }

        $content = file_get_contents($path);
        $oldValue = env($key);

        if ($oldValue === null) {
            // Variable doesn't exist, add it
            $content .= "\n{$key}={$value}";
        } else {
            // Variable exists, replace it
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $content
            );
        }

        file_put_contents($path, $content);

        return true;
    }
}
