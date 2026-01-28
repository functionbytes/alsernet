<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\Setting;

class UrlSettingController extends Controller
{
    /**
     * Display the URL settings page.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.urls');

        // Fetch all URL-related settings as key-value pairs
        $settings = Setting::where('category', 'urls')
            ->get()
            ->mapWithKeys(fn ($setting) => [$setting->key => $setting->value])
            ->all();

        return view('mailing::settings.url-settings.index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Store URL settings.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.urls');

        try {
            $validated = $request->validate([
                // Application URLs
                'app_url' => 'required|url',
                'admin_url' => 'required|url',
                'api_url' => 'required|url',

                // Tracking URLs
                'tracking_domain' => 'required|url',
                'tracking_pixel_base_url' => 'required|url',
                'click_tracking_base_url' => 'required|url',
                'open_tracking_base_url' => 'required|url',
                'unsubscribe_tracking_url' => 'required|url',

                // Webhook URLs
                'bounce_webhook_url' => 'nullable|url',
                'feedback_loop_webhook_url' => 'nullable|url',
                'complaint_webhook_url' => 'nullable|url',
                'delivery_webhook_url' => 'nullable|url',
                'custom_webhook_url' => 'nullable|url',

                // Redirect URLs
                'unsubscribe_redirect_url' => 'required|url',
                'preference_center_url' => 'nullable|url',
                'email_verification_url' => 'nullable|url',

                // DNS/Domain URLs
                'dkim_domain' => 'nullable|string|max:255',
                'spf_domain' => 'nullable|string|max:255',
                'dmarc_policy_url' => 'nullable|url',

                // Custom tracking parameters
                'utm_source' => 'nullable|string|max:255',
                'utm_medium' => 'nullable|string|max:255',
                'utm_campaign' => 'nullable|string|max:255',
                'utm_content' => 'nullable|string|max:255',
                'utm_term' => 'nullable|string|max:255',
            ]);

            // Save each setting to the database
            foreach ($validated as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key, 'category' => 'urls'],
                    ['value' => $value]
                );
            }

            return redirect()
                ->route('settings.mailing.settings.urls.index')
                ->with('success', 'Configuración de URLs actualizada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al guardar: '.$e->getMessage());
        }
    }

    /**
     * Update URL settings.
     */
    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.urls');

        try {
            $validated = $request->validate([
                // Application URLs
                'app_url' => 'required|url',
                'admin_url' => 'required|url',
                'api_url' => 'required|url',

                // Tracking URLs
                'tracking_domain' => 'required|url',
                'tracking_pixel_base_url' => 'required|url',
                'click_tracking_base_url' => 'required|url',
                'open_tracking_base_url' => 'required|url',
                'unsubscribe_tracking_url' => 'required|url',

                // Webhook URLs
                'bounce_webhook_url' => 'nullable|url',
                'feedback_loop_webhook_url' => 'nullable|url',
                'complaint_webhook_url' => 'nullable|url',
                'delivery_webhook_url' => 'nullable|url',
                'custom_webhook_url' => 'nullable|url',

                // Redirect URLs
                'unsubscribe_redirect_url' => 'required|url',
                'preference_center_url' => 'nullable|url',
                'email_verification_url' => 'nullable|url',

                // DNS/Domain URLs
                'dkim_domain' => 'nullable|string|max:255',
                'spf_domain' => 'nullable|string|max:255',
                'dmarc_policy_url' => 'nullable|url',

                // Custom tracking parameters
                'utm_source' => 'nullable|string|max:255',
                'utm_medium' => 'nullable|string|max:255',
                'utm_campaign' => 'nullable|string|max:255',
                'utm_content' => 'nullable|string|max:255',
                'utm_term' => 'nullable|string|max:255',
            ]);

            // Update each setting in the database
            foreach ($validated as $key => $value) {
                Setting::where('key', $key)
                    ->where('category', 'urls')
                    ->update(['value' => $value]);

                // If setting doesn't exist, create it
                if (! Setting::where('key', $key)->where('category', 'urls')->exists()) {
                    Setting::create([
                        'key' => $key,
                        'category' => 'urls',
                        'value' => $value,
                    ]);
                }
            }

            return redirect()
                ->route('settings.mailing.settings.urls.index')
                ->with('success', 'Configuración de URLs actualizada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar: '.$e->getMessage());
        }
    }
}
