<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use App\Models\MailingSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;

class ApiSettingsController extends Controller
{
    /**
     * Display the API settings page.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.manage');

        $settings = MailingSetting::getInstance();

        $apiSettings = [
            'api_key' => $settings->api_key ?? config('mailing.api_key'),
            'api_url' => $settings->api_url ?? config('mailing.api_url'),
            'cache_enabled' => $settings->cache_enabled ?? config('mailing.cache.enabled'),
            'cache_ttl' => $settings->cache_ttl ?? config('mailing.cache.ttl.subscribers'),
            'retry_enabled' => $settings->retry_enabled ?? (config('mailing.retry.max_attempts', 0) > 0),
        ];

        // Try to get account info if connected
        $accountInfo = null;
        if ($apiSettings['api_key'] && $apiSettings['api_url']) {
            try {
                $endpoints = ['account', 'ping', 'status', 'v1/account'];
                $baseUrl = rtrim($apiSettings['api_url'], '/');

                foreach ($endpoints as $endpoint) {
                    try {
                        $response = Http::timeout(10)
                            ->withHeaders([
                                'X-AUTH-TOKEN' => $apiSettings['api_key'],
                            ])
                            ->get($baseUrl.'/'.$endpoint);

                        if ($response->successful()) {
                            $data = $response->json();
                            $accountInfo = [
                                'name' => $data['name'] ?? $data['account_name'] ?? 'Account',
                                'plan' => $data['plan'] ?? 'Unknown',
                                'credits' => $data['credits'] ?? 0,
                                'status' => $data['status'] ?? 'active',
                            ];
                            break;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            } catch (\Exception $e) {
                // Connection failed - leave accountInfo as null
            }
        }

        return view('mailing::settings.api', compact('apiSettings', 'accountInfo'));
    }

    /**
     * Update API credentials.
     */
    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.manage');

        $validated = $request->validate([
            'api_key' => 'required|string',
            'api_url' => 'required|url',
            'cache_enabled' => 'boolean',
            'cache_ttl' => 'nullable|integer|min:1|max:1440',
            'retry_enabled' => 'boolean',
        ]);

        try {
            // Save settings to database
            $settings = MailingSetting::getInstance();
            $settings->update([
                'api_key' => $validated['api_key'],
                'api_url' => $validated['api_url'],
                'cache_enabled' => $validated['cache_enabled'] ?? false,
                'cache_ttl' => ($validated['cache_ttl'] ?? 60) * 60, // Convert minutes to seconds
                'retry_enabled' => $validated['retry_enabled'] ?? false,
            ]);

            // Also update config at runtime for this request
            config([
                'mailing.api_key' => $validated['api_key'],
                'mailing.api_url' => $validated['api_url'],
                'mailing.cache.enabled' => $validated['cache_enabled'] ?? false,
                'mailing.cache.ttl.subscribers' => ($validated['cache_ttl'] ?? 60) * 60,
                'mailing.retry.max_attempts' => $validated['retry_enabled'] ? 3 : 0,
            ]);

            return redirect()
                ->route('settings.mailing.api.index')
                ->with('success', 'Configuración de API guardada correctamente');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Test connection with Mailrelay API.
     */
    public function testConnection(Request $request): JsonResponse
    {
        Gate::authorize('mailing.settings.manage');

        $apiKey = $request->input('api_key');
        $apiUrl = rtrim($request->input('api_url'), '/');

        if (! $apiKey || ! $apiUrl) {
            return response()->json([
                'success' => false,
                'message' => 'API Key y URL son requeridos',
            ], 422);
        }

        try {
            // Try multiple endpoints to find the correct one
            $endpoints = [
                'account',    // Standard endpoint
                'ping',       // Health check
                'status',     // Status endpoint
                'v1/account', // Alternative v1 path
            ];

            $lastError = null;

            foreach ($endpoints as $endpoint) {
                try {
                    $testUrl = $apiUrl.'/'.$endpoint;
                    $response = Http::timeout(10)
                        ->withHeaders([
                            'X-AUTH-TOKEN' => $apiKey,
                        ])
                        ->get($testUrl);

                    if ($response->successful()) {
                        $data = $response->json();

                        return response()->json([
                            'success' => true,
                            'message' => 'Conexión exitosa con MailRelay',
                            'account_info' => [
                                'account_name' => $data['name'] ?? $data['account_name'] ?? 'Cuenta de Mailrelay',
                                'plan' => $data['plan'] ?? 'Desconocido',
                                'credits' => $data['credits'] ?? 0,
                            ],
                        ]);
                    }
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    continue;
                }
            }

            // If no endpoint worked, return a generic error
            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con los endpoints disponibles. Verifica la URL y la API Key. Error: '.$lastError,
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: '.$e->getMessage(),
            ], 400);
        }
    }
}
