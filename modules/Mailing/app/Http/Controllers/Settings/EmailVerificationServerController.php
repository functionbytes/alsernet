<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\EmailVerificationServer;

class EmailVerificationServerController extends Controller
{
    /**
     * Display a listing of email verification servers.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.email-verification-servers.view');

        $verificationServers = EmailVerificationServer::where('user_id', auth()->id())
            ->orderBy('name')
            ->paginate(15);

        return view('mailing::settings.email-verification-servers.index', [
            'verificationServers' => $verificationServers,
        ]);
    }

    /**
     * Show the form for creating a new email verification server.
     */
    public function create(): View
    {
        Gate::authorize('mailing.settings.email-verification-servers.create');

        return view('mailing::settings.email-verification-servers.create');
    }

    /**
     * Store a newly created email verification server.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.email-verification-servers.create');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:zerobounce,neverbounce,kickbox,clearout,debounce,custom',
                'status' => 'required|in:active,inactive,error',

                // API configuration
                'api_key' => 'required|string',
                'api_url' => 'nullable|url',
                'api_options' => 'nullable|json',

                // Verification settings
                'check_disposable' => 'boolean',
                'check_role_based' => 'boolean',
                'check_smtp' => 'boolean',
                'check_catch_all' => 'boolean',

                // Rate limiting
                'requests_per_minute' => 'nullable|integer|min:1|max:10000',
                'requests_per_day' => 'nullable|integer|min:1|max:1000000',

                // Credit management
                'credit_threshold' => 'nullable|integer|min:1|max:100',
            ]);

            $validated['user_id'] = auth()->id();
            $validated['status'] = $validated['status'] ?? 'inactive';

            EmailVerificationServer::create($validated);

            return redirect()
                ->route('settings.mailing.verification-servers.index')
                ->with('success', 'Servidor de verificación de correo creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el servidor de verificación: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified email verification server.
     */
    public function edit(int $id): View
    {
        Gate::authorize('mailing.settings.email-verification-servers.edit');

        $verificationServer = EmailVerificationServer::where('user_id', auth()->id())
            ->findOrFail($id);

        return view('mailing::settings.email-verification-servers.edit', [
            'verificationServer' => $verificationServer,
        ]);
    }

    /**
     * Update the specified email verification server.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.email-verification-servers.edit');

        try {
            $verificationServer = EmailVerificationServer::where('user_id', auth()->id())
                ->findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:zerobounce,neverbounce,kickbox,clearout,debounce,custom',
                'status' => 'required|in:active,inactive,error',

                // API configuration
                'api_key' => 'required|string',
                'api_url' => 'nullable|url',
                'api_options' => 'nullable|json',

                // Verification settings
                'check_disposable' => 'boolean',
                'check_role_based' => 'boolean',
                'check_smtp' => 'boolean',
                'check_catch_all' => 'boolean',

                // Rate limiting
                'requests_per_minute' => 'nullable|integer|min:1|max:10000',
                'requests_per_day' => 'nullable|integer|min:1|max:1000000',

                // Credit management
                'credit_threshold' => 'nullable|integer|min:1|max:100',
            ]);

            $verificationServer->update($validated);

            return redirect()
                ->route('settings.mailing.verification-servers.index')
                ->with('success', 'Servidor de verificación de correo actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el servidor de verificación: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified email verification server.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.email-verification-servers.delete');

        try {
            $verificationServer = EmailVerificationServer::where('user_id', auth()->id())
                ->findOrFail($id);

            $verificationServer->delete();

            return redirect()
                ->route('settings.mailing.verification-servers.index')
                ->with('success', 'Servidor de verificación de correo eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el servidor de verificación: '.$e->getMessage());
        }
    }

    /**
     * Test the connection to the email verification service.
     */
    public function testService(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.email-verification-servers.edit');

        try {
            $verificationServer = EmailVerificationServer::where('user_id', auth()->id())
                ->findOrFail($id);

            // Build test request based on service type
            $testEmail = 'test@example.com';
            $response = $this->callVerificationApi($verificationServer, $testEmail);

            if (! $response['success']) {
                throw new \Exception('Error en la API de verificación: '.$response['message']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Conexión establecida correctamente.',
                'test_result' => $response['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al probar el servicio: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check the remaining credits/quota for the verification service.
     */
    public function checkCredits(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.email-verification-servers.edit');

        try {
            $verificationServer = EmailVerificationServer::where('user_id', auth()->id())
                ->findOrFail($id);

            $credits = $this->fetchCredits($verificationServer);

            // Update credits in database
            $verificationServer->update([
                'credits_available' => $credits['available'] ?? null,
                'credits_used' => $credits['used'] ?? 0,
                'credits_last_checked_at' => now(),
            ]);

            $warning = false;
            $warningMessage = '';

            if ($credits['available'] && $verificationServer->credit_threshold) {
                $percentageUsed = ($credits['used'] / $credits['available']) * 100;

                if ($percentageUsed >= $verificationServer->credit_threshold) {
                    $warning = true;
                    $warningMessage = "Has alcanzado el {$verificationServer->credit_threshold}% de tu límite de créditos.";
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Créditos verificados correctamente.',
                'credits' => $credits,
                'warning' => $warning,
                'warning_message' => $warningMessage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar créditos: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Call the verification API based on service type.
     */
    private function callVerificationApi(EmailVerificationServer $server, string $email): array
    {
        try {
            $apiUrl = $server->api_url ?? $this->getDefaultApiUrl($server->type);
            $params = $this->getApiParams($server->type, $email, $server->api_key);

            $response = Http::timeout(30)->get($apiUrl, $params);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => 'API response error: '.$response->status(),
                ];
            }

            return [
                'success' => true,
                'message' => 'Verification successful',
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch remaining credits from the verification service.
     */
    private function fetchCredits(EmailVerificationServer $server): array
    {
        try {
            $apiUrl = $server->api_url ?? $this->getDefaultApiUrl($server->type);

            // Append credits endpoint based on service type
            $creditsEndpoint = match ($server->type) {
                'zerobounce' => $apiUrl.'/getCredits',
                'neverbounce' => $apiUrl.'/account/credits',
                'kickbox' => $apiUrl.'/account',
                'clearout' => $apiUrl.'/account',
                'debounce' => $apiUrl.'/api/user/account',
                default => $apiUrl.'/account',
            };

            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => 'Bearer '.$server->api_key])
                ->get($creditsEndpoint);

            if (! $response->successful()) {
                throw new \Exception('Unable to fetch credits');
            }

            return $this->parseCreditsResponse($server->type, $response->json());
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch credits: '.$e->getMessage());
        }
    }

    /**
     * Parse credits response based on service type.
     */
    private function parseCreditsResponse(string $type, array $response): array
    {
        return match ($type) {
            'zerobounce' => [
                'available' => $response['Credits'] ?? 0,
                'used' => 0,
            ],
            'neverbounce' => [
                'available' => $response['balance'] ?? 0,
                'used' => 0,
            ],
            'kickbox' => [
                'available' => $response['balance'] ?? 0,
                'used' => 0,
            ],
            'clearout' => [
                'available' => $response['credits'] ?? 0,
                'used' => 0,
            ],
            'debounce' => [
                'available' => $response['credits'] ?? 0,
                'used' => 0,
            ],
            default => [
                'available' => null,
                'used' => 0,
            ],
        };
    }

    /**
     * Get default API URL for verification service.
     */
    private function getDefaultApiUrl(string $type): string
    {
        return match ($type) {
            'zerobounce' => 'https://api.zerobounce.net/v2/validate',
            'neverbounce' => 'https://api.neverbounce.com/v4/single/check',
            'kickbox' => 'https://api.kickbox.io/v2/verify',
            'clearout' => 'https://api.clearout.io/v2/email_verify/instant',
            'debounce' => 'https://api.debounce.io/v1/validate',
            default => '',
        };
    }

    /**
     * Get API parameters based on service type.
     */
    private function getApiParams(string $type, string $email, string $apiKey): array
    {
        return match ($type) {
            'zerobounce' => ['email' => $email, 'api_key' => $apiKey],
            'neverbounce' => ['email' => $email, 'api_token' => $apiKey],
            'kickbox' => ['email' => $email],
            'clearout' => ['email' => $email],
            'debounce' => ['email' => $email, 'api' => $apiKey],
            default => ['email' => $email, 'api_key' => $apiKey],
        };
    }
}
