<?php

namespace Modules\Mailrelay\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Mailrelay\Entities\MailrelaySettings;
use Modules\Mailrelay\Http\Controllers\Controller;

class ApiSettingsController extends Controller
{
    /**
     * Display the API settings page.
     */
    public function index(): View
    {
        Gate::authorize('mailrelay.settings.manage');

        $settings = MailrelaySettings::instance();

        $apiSettings = [
            'api_key' => $settings->api_key ?? config('mailrelay.api_key'),
            'api_url' => $settings->api_url ?? config('mailrelay.api_url'),
            'cache_enabled' => $settings->cache_enabled ?? config('mailrelay.cache.enabled'),
            'cache_ttl' => $settings->cache_ttl ?? config('mailrelay.cache.ttl.subscribers', 60),
            'retry_enabled' => $settings->retry_enabled ?? (config('mailrelay.retry.max_attempts', 0) > 0),
        ];

        // Try to get account info if connected
        $accountInfo = null;
        if ($apiSettings['api_key'] && $apiSettings['api_url']) {
            try {
                $response = Http::timeout($settings->timeout ?? 30)
                    ->withHeaders([
                        'X-AUTH-TOKEN' => $apiSettings['api_key'],
                    ])
                    ->get($apiSettings['api_url'].'/account');

                if ($response->successful()) {
                    $data = $response->json();
                    $accountInfo = [
                        'account_name' => $data['name'] ?? $data['account_name'] ?? 'Account',
                        'plan' => $data['plan'] ?? 'Unknown',
                        'credits' => $data['credits'] ?? 0,
                        'status' => $data['status'] ?? 'active',
                    ];
                }
            } catch (\Exception $e) {
                // Connection failed - leave accountInfo as null
            }
        }

        return view('mailrelay::settings.api', compact('apiSettings', 'accountInfo'));
    }

    /**
     * Update API credentials.
     */
    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('mailrelay.settings.manage');

        $validated = $request->validate([
            'api_key' => 'required|string',
            'api_url' => 'required|url',
            'cache_enabled' => 'boolean',
            'cache_ttl' => 'nullable|integer|min:1|max:1440',
            'retry_enabled' => 'boolean',
        ]);

        try {
            // Convert checkbox values to boolean
            $validated['cache_enabled'] = $request->has('cache_enabled');
            $validated['retry_enabled'] = $request->has('retry_enabled');

            // Save settings to database using MailrelaySettings model
            MailrelaySettings::updateSettings($validated);

            return redirect()
                ->route('settings.mailrelay.api.index')
                ->with('success', 'Configuración de API guardada correctamente');
        } catch (\Exception $e) {
            Log::error('Mailrelay API settings failed', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Ha ocurrido un error. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Test connection with Mailrelay API.
     */
    public function testConnection(Request $request): JsonResponse
    {
        Gate::authorize('mailrelay.settings.manage');

        $apiKey = $request->input('api_key');
        $apiUrl = $request->input('api_url');

        if (! $apiKey || ! $apiUrl) {
            return response()->json([
                'success' => false,
                'message' => 'API Key y URL son requeridos',
            ], 422);
        }

        try {
            // Test Mailrelay API connection using /account endpoint
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-AUTH-TOKEN' => $apiKey,
                ])
                ->get($apiUrl.'/account');

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'message' => 'Conexión exitosa con MailRelay',
                    'account_info' => [
                        'account_name' => $data['name'] ?? $data['account_name'] ?? 'Cuenta',
                        'plan' => $data['plan'] ?? 'Desconocido',
                        'credits' => $data['credits'] ?? 0,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error HTTP '.$response->status().': '.$response->reason(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión. Por favor, inténtalo de nuevo.',
            ], 400);
        }
    }
}
