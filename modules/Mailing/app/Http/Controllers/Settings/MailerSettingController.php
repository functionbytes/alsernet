<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\Setting;

class MailerSettingController extends Controller
{
    /**
     * Display the mailer settings page.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.mailer');

        // Fetch all mailer-related settings as key-value pairs
        $settings = Setting::where('category', 'mailer')
            ->get()
            ->mapWithKeys(fn ($setting) => [$setting->key => $setting->value])
            ->all();

        return view('mailing::settings.mailer-settings.index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Store mailer settings.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.mailer');

        try {
            $validated = $request->validate([
                // Email addresses
                'from_email' => 'required|email|max:255',
                'from_name' => 'required|string|max:255',
                'reply_to_email' => 'nullable|email|max:255',
                'return_path_email' => 'nullable|email|max:255',
                'bounce_email' => 'nullable|email|max:255',

                // DKIM Configuration
                'dkim_enabled' => 'boolean',
                'dkim_selector' => 'nullable|string|max:255',
                'dkim_private_key' => 'nullable|string',
                'dkim_public_key' => 'nullable|string',
                'dkim_domain' => 'nullable|string|max:255',

                // SPF Configuration
                'spf_enabled' => 'boolean',
                'spf_record' => 'nullable|string|max:1000',

                // DMARC Configuration
                'dmarc_enabled' => 'boolean',
                'dmarc_policy' => 'nullable|in:none,quarantine,reject',
                'dmarc_subdomain_policy' => 'nullable|in:none,quarantine,reject',
                'dmarc_rua_email' => 'nullable|email|max:255',
                'dmarc_ruf_email' => 'nullable|email|max:255',
                'dmarc_percentage' => 'nullable|integer|min:0|max:100',
                'dmarc_forensics' => 'boolean',

                // General mailer settings
                'mailer_type' => 'required|in:smtp,sendgrid,mailgun,ses,postmark,sparkpost',
                'default_sending_server_id' => 'nullable|integer',
                'enable_reply_tracking' => 'boolean',
                'enable_bounce_handling' => 'boolean',
                'enable_complaint_handling' => 'boolean',
                'enable_delivery_tracking' => 'boolean',

                // Email headers
                'custom_headers_enabled' => 'boolean',
                'custom_headers_json' => 'nullable|json',

                // Signing
                'arc_enabled' => 'boolean',
                'arc_selector' => 'nullable|string|max:255',
                'arc_instance' => 'nullable|integer',

                // Throttling
                'throttle_enabled' => 'boolean',
                'throttle_per_second' => 'nullable|integer|min:1',
                'throttle_per_minute' => 'nullable|integer|min:1',
                'throttle_per_hour' => 'nullable|integer|min:1',
            ]);

            // Convert checkbox values
            $validated['dkim_enabled'] = $request->has('dkim_enabled');
            $validated['spf_enabled'] = $request->has('spf_enabled');
            $validated['dmarc_enabled'] = $request->has('dmarc_enabled');
            $validated['dmarc_forensics'] = $request->has('dmarc_forensics');
            $validated['enable_reply_tracking'] = $request->has('enable_reply_tracking');
            $validated['enable_bounce_handling'] = $request->has('enable_bounce_handling');
            $validated['enable_complaint_handling'] = $request->has('enable_complaint_handling');
            $validated['enable_delivery_tracking'] = $request->has('enable_delivery_tracking');
            $validated['custom_headers_enabled'] = $request->has('custom_headers_enabled');
            $validated['arc_enabled'] = $request->has('arc_enabled');
            $validated['throttle_enabled'] = $request->has('throttle_enabled');

            // Save each setting to the database
            foreach ($validated as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key, 'category' => 'mailer'],
                    ['value' => $value]
                );
            }

            return redirect()
                ->route('settings.mailing.settings.mailer.index')
                ->with('success', 'Configuración del servidor de correo actualizada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al guardar: '.$e->getMessage());
        }
    }

    /**
     * Update mailer settings.
     */
    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.mailer');

        try {
            $validated = $request->validate([
                // Email addresses
                'from_email' => 'required|email|max:255',
                'from_name' => 'required|string|max:255',
                'reply_to_email' => 'nullable|email|max:255',
                'return_path_email' => 'nullable|email|max:255',
                'bounce_email' => 'nullable|email|max:255',

                // DKIM Configuration
                'dkim_enabled' => 'boolean',
                'dkim_selector' => 'nullable|string|max:255',
                'dkim_private_key' => 'nullable|string',
                'dkim_public_key' => 'nullable|string',
                'dkim_domain' => 'nullable|string|max:255',

                // SPF Configuration
                'spf_enabled' => 'boolean',
                'spf_record' => 'nullable|string|max:1000',

                // DMARC Configuration
                'dmarc_enabled' => 'boolean',
                'dmarc_policy' => 'nullable|in:none,quarantine,reject',
                'dmarc_subdomain_policy' => 'nullable|in:none,quarantine,reject',
                'dmarc_rua_email' => 'nullable|email|max:255',
                'dmarc_ruf_email' => 'nullable|email|max:255',
                'dmarc_percentage' => 'nullable|integer|min:0|max:100',
                'dmarc_forensics' => 'boolean',

                // General mailer settings
                'mailer_type' => 'required|in:smtp,sendgrid,mailgun,ses,postmark,sparkpost',
                'default_sending_server_id' => 'nullable|integer',
                'enable_reply_tracking' => 'boolean',
                'enable_bounce_handling' => 'boolean',
                'enable_complaint_handling' => 'boolean',
                'enable_delivery_tracking' => 'boolean',

                // Email headers
                'custom_headers_enabled' => 'boolean',
                'custom_headers_json' => 'nullable|json',

                // Signing
                'arc_enabled' => 'boolean',
                'arc_selector' => 'nullable|string|max:255',
                'arc_instance' => 'nullable|integer',

                // Throttling
                'throttle_enabled' => 'boolean',
                'throttle_per_second' => 'nullable|integer|min:1',
                'throttle_per_minute' => 'nullable|integer|min:1',
                'throttle_per_hour' => 'nullable|integer|min:1',
            ]);

            // Convert checkbox values
            $validated['dkim_enabled'] = $request->has('dkim_enabled');
            $validated['spf_enabled'] = $request->has('spf_enabled');
            $validated['dmarc_enabled'] = $request->has('dmarc_enabled');
            $validated['dmarc_forensics'] = $request->has('dmarc_forensics');
            $validated['enable_reply_tracking'] = $request->has('enable_reply_tracking');
            $validated['enable_bounce_handling'] = $request->has('enable_bounce_handling');
            $validated['enable_complaint_handling'] = $request->has('enable_complaint_handling');
            $validated['enable_delivery_tracking'] = $request->has('enable_delivery_tracking');
            $validated['custom_headers_enabled'] = $request->has('custom_headers_enabled');
            $validated['arc_enabled'] = $request->has('arc_enabled');
            $validated['throttle_enabled'] = $request->has('throttle_enabled');

            // Update each setting in the database
            foreach ($validated as $key => $value) {
                Setting::where('key', $key)
                    ->where('category', 'mailer')
                    ->update(['value' => $value]);

                // If setting doesn't exist, create it
                if (! Setting::where('key', $key)->where('category', 'mailer')->exists()) {
                    Setting::create([
                        'key' => $key,
                        'category' => 'mailer',
                        'value' => $value,
                    ]);
                }
            }

            return redirect()
                ->route('settings.mailing.settings.mailer.index')
                ->with('success', 'Configuración del servidor de correo actualizada correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar: '.$e->getMessage());
        }
    }
}
