<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;

class GeneralSettingsController extends Controller
{
    /**
     * Display the general settings page.
     */
    public function index()
    {
        Gate::authorize('mailing.settings.general');

        $settings = (object) [
            'sender_name' => config('mailing.sender.name'),
            'sender_email' => config('mailing.sender.email'),
            'reply_to_email' => config('mailing.sender.reply_to'),
            'auto_sync_enabled' => config('mailing.sync.auto_sync', false),
            'sync_frequency' => config('mailing.sync.batch_interval', 60),
            'sync_deleted' => config('mailing.sync.sync_deleted', false),
            'emails_per_campaign' => config('mailing.campaign.emails_per_campaign', 1000),
            'retry_attempts' => config('mailing.retry.max_attempts', 3),
            'timeout' => config('mailing.timeout', 30),
            'double_optin' => config('mailing.privacy.double_optin', false),
            'allow_unsubscribe' => config('mailing.privacy.allow_unsubscribe', true),
            'unsubscribe_footer' => config('mailing.privacy.unsubscribe_footer', ''),
            'detailed_logging' => config('mailing.logging.enabled', false),
            'log_retention_days' => config('mailing.logging.retention_days', 30),
            'sandbox_mode' => config('mailing.testing.sandbox_mode', false),
        ];

        return view('mailing::settings.general', compact('settings'))
            ->withErrors(session()->get('errors', new \Illuminate\Support\MessageBag()));
    }

    /**
     * Update general settings.
     */
    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.general');

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
                'mailing.sender.name' => $validated['sender_name'],
                'mailing.sender.email' => $validated['sender_email'],
                'mailing.sender.reply_to' => $validated['reply_to_email'],
                'mailing.sync.auto_sync' => $validated['auto_sync_enabled'],
                'mailing.sync.batch_interval' => $validated['sync_frequency'],
                'mailing.sync.sync_deleted' => $validated['sync_deleted'],
                'mailing.campaign.emails_per_campaign' => $validated['emails_per_campaign'],
                'mailing.retry.max_attempts' => $validated['retry_attempts'],
                'mailing.timeout' => $validated['timeout'],
                'mailing.privacy.double_optin' => $validated['double_optin'],
                'mailing.privacy.allow_unsubscribe' => $validated['allow_unsubscribe'],
                'mailing.privacy.unsubscribe_footer' => $validated['unsubscribe_footer'],
                'mailing.logging.enabled' => $validated['detailed_logging'],
                'mailing.logging.retention_days' => $validated['log_retention_days'],
                'mailing.testing.sandbox_mode' => $validated['sandbox_mode'],
            ]);

            // TODO: Persist settings to database using a MailingSettings model
            // or write to .env file using a helper service

            return redirect()
                ->route('settings.mailing.general.index')
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
