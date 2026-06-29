<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Modules\Mailrelay\Entities\MailrelaySettings;
use Modules\Mailrelay\Http\Controllers\Controller;

class GeneralSettingsController extends Controller
{
    /**
     * Display the general settings page.
     */
    public function index()
    {
        Gate::authorize('mailrelay.settings.general');

        // Get settings from database
        $settings = MailrelaySettings::instance();

        return view('mailrelay::settings.general', compact('settings'))
            ->withErrors(session()->get('errors', new MessageBag));
    }

    /**
     * Update general settings.
     */
    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.general');

        try {
            $validated = $request->validate([
                // Sender information
                'sender_name' => 'required|string|max:100',
                'sender_email' => 'required|email|max:100',
                'reply_to_email' => 'nullable|email|max:100',

                // Sync options
                'auto_sync_enabled' => 'boolean',
                'sync_frequency' => 'required_if:auto_sync_enabled,1|in:15,30,60,360,1440',
                'sync_deleted' => 'boolean',

                // Limits and restrictions
                'emails_per_campaign' => 'required|integer|min:1',
                'retry_attempts' => 'required|integer|min:0|max:5',
                'timeout' => 'required|integer|min:10|max:300',

                // Privacy options
                'double_optin' => 'boolean',
                'allow_unsubscribe' => 'boolean',
                'unsubscribe_footer' => 'nullable|string|max:500',

                // Advanced settings
                'detailed_logging' => 'boolean',
                'log_retention_days' => 'required|integer|min:1|max:365',
                'sandbox_mode' => 'boolean',
            ]);

            // Convert checkbox values to boolean
            $validated['auto_sync_enabled'] = $request->has('auto_sync_enabled');
            $validated['sync_deleted'] = $request->has('sync_deleted');
            $validated['double_optin'] = $request->has('double_optin');
            $validated['allow_unsubscribe'] = $request->has('allow_unsubscribe');
            $validated['detailed_logging'] = $request->has('detailed_logging');
            $validated['sandbox_mode'] = $request->has('sandbox_mode');

            // Persist settings to database using MailrelaySettings model
            MailrelaySettings::updateSettings($validated);

            return redirect()
                ->route('settings.mailrelay.general.index')
                ->with('success', 'Configuración actualizada correctamente');
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Por favor, corrige los errores en el formulario.');
        } catch (\Exception $e) {
            Log::error('Mailrelay general settings save failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al guardar. Por favor, inténtalo de nuevo.');
        }
    }
}
