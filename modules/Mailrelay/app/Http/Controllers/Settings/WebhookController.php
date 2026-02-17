<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Mailrelay\Http\Controllers\Controller;
use Modules\Mailrelay\Models\MailrelayWebhook;

class WebhookController extends Controller
{
    /**
     * Display a listing of webhooks.
     */
    public function index(): View
    {
        dd('GeneralSettingsController@index');
        $webhooks = MailrelayWebhook::orderBy('created_at', 'desc')->get();

        return view('mailrelay::settings.webhooks.index', [
            'webhooks' => $webhooks,
        ]);
    }

    /**
     * Show the form for creating a new webhook.
     */
    public function create(): View
    {
        return view('mailrelay::settings.webhooks.create');
    }

    /**
     * Store a newly created webhook.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'url' => 'required|url|max:255',
                'event_type' => 'required|string|max:100',
                'secret' => 'nullable|string|max:255',
                'active' => 'boolean',
                'verify_ssl' => 'boolean',
            ]);

            MailrelayWebhook::create($validated);

            return redirect()
                ->route('settings.mailrelay.webhooks.index')
                ->with('success', 'Webhook creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el webhook: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified webhook.
     */
    public function edit(int $id): View
    {
        $webhook = MailrelayWebhook::findOrFail($id);

        return view('mailrelay::settings.webhooks.edit', [
            'webhook' => $webhook,
        ]);
    }

    /**
     * Update the specified webhook.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        try {
            $webhook = MailrelayWebhook::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'url' => 'required|url|max:255',
                'event_type' => 'required|string|max:100',
                'secret' => 'nullable|string|max:255',
                'active' => 'boolean',
                'verify_ssl' => 'boolean',
            ]);

            $webhook->update($validated);

            return redirect()
                ->route('settings.mailrelay.webhooks.index')
                ->with('success', 'Webhook actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el webhook: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified webhook.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $webhook = MailrelayWebhook::findOrFail($id);
            $webhook->delete();

            return redirect()
                ->route('settings.mailrelay.webhooks.index')
                ->with('success', 'Webhook eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el webhook: '.$e->getMessage());
        }
    }

    /**
     * Test the specified webhook.
     */
    public function test(int $id): JsonResponse
    {
        try {
            $webhook = MailrelayWebhook::findOrFail($id);

            $testPayload = [
                'event' => $webhook->event_type,
                'test' => true,
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'message' => 'This is a test webhook call from Mailrelay settings.',
                ],
            ];

            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Mailrelay-Webhook-Test/1.0',
            ];

            if ($webhook->secret !== null) {
                $headers['X-Webhook-Signature'] = hash_hmac('sha256', json_encode($testPayload), $webhook->secret);
            }

            $response = Http::timeout(10);

            if (! $webhook->verify_ssl) {
                $response = $response->withoutVerifying();
            }

            $response = $response->withHeaders($headers)
                ->post($webhook->url, $testPayload);

            $webhook->update([
                'last_called_at' => now(),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook probado correctamente.',
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'El webhook respondió con un error.',
                'status' => $response->status(),
                'response' => $response->body(),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al probar el webhook: '.$e->getMessage(),
            ], 500);
        }
    }
}
