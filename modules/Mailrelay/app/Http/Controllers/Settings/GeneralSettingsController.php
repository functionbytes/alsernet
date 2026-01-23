<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailrelay\Http\Controllers\Controller;

class GeneralSettingsController extends Controller
{
    /**
     * Display the general settings page.
     */
    public function index()
    {
        Gate::authorize('mailrelay.settings.general');

        $settings = (object) [
            'sender_name' => config('mailrelay.sender.name'),
            'sender_email' => config('mailrelay.sender.email'),
            'reply_to_email' => config('mailrelay.sender.reply_to'),
            'auto_sync_enabled' => config('mailrelay.sync.auto_sync', false),
            'sync_frequency' => config('mailrelay.sync.batch_interval', 60),
            'sync_deleted' => config('mailrelay.sync.sync_deleted', false),
            'emails_per_campaign' => config('mailrelay.campaign.emails_per_campaign', 1000),
            'retry_attempts' => config('mailrelay.retry.max_attempts', 3),
            'timeout' => config('mailrelay.timeout', 30),
            'double_optin' => config('mailrelay.privacy.double_optin', false),
            'allow_unsubscribe' => config('mailrelay.privacy.allow_unsubscribe', true),
            'unsubscribe_footer' => config('mailrelay.privacy.unsubscribe_footer', ''),
            'detailed_logging' => config('mailrelay.logging.enabled', false),
            'log_retention_days' => config('mailrelay.logging.retention_days', 30),
            'sandbox_mode' => config('mailrelay.testing.sandbox_mode', false),
        ];

        return view('mailrelay::settings.general', compact('settings'))
            ->withErrors(session()->get('errors', new \Illuminate\Support\MessageBag()));
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

            // Save settings to configuration or database
            // In a real scenario, you would save these to a settings table or update .env
            // For now, we'll use runtime config updates
            Config::set([
                'mailrelay.sender.name' => $validated['sender_name'],
                'mailrelay.sender.email' => $validated['sender_email'],
                'mailrelay.sender.reply_to' => $validated['reply_to_email'],
                'mailrelay.sync.auto_sync' => $validated['auto_sync_enabled'],
                'mailrelay.sync.batch_interval' => $validated['sync_frequency'],
                'mailrelay.sync.sync_deleted' => $validated['sync_deleted'],
                'mailrelay.campaign.emails_per_campaign' => $validated['emails_per_campaign'],
                'mailrelay.retry.max_attempts' => $validated['retry_attempts'],
                'mailrelay.timeout' => $validated['timeout'],
                'mailrelay.privacy.double_optin' => $validated['double_optin'],
                'mailrelay.privacy.allow_unsubscribe' => $validated['allow_unsubscribe'],
                'mailrelay.privacy.unsubscribe_footer' => $validated['unsubscribe_footer'],
                'mailrelay.logging.enabled' => $validated['detailed_logging'],
                'mailrelay.logging.retention_days' => $validated['log_retention_days'],
                'mailrelay.testing.sandbox_mode' => $validated['sandbox_mode'],
            ]);

            // TODO: Persist settings to database using a MailrelaySettings model
            // or write to .env file using a helper service

            return redirect()
                ->route('settings.mailrelay.general.index')
                ->with('success', 'Configuración actualizada correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Por favor, corrige los errores en el formulario.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al guardar: '.$e->getMessage());
        }
    }
}
