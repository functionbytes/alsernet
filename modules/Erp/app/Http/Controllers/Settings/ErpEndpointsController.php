<?php

namespace Modules\Erp\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\ErpCredential;
use Modules\Erp\Models\ErpEndpoint;
use Modules\Erp\Models\ErpEndpointLog;
use Modules\Erp\Models\ErpEndpointToken;
use Modules\Erp\Support\ErpEndpointUrlGuard;

class ErpEndpointsController extends Controller
{
    /**
     * Display a listing of endpoints
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'configured');
        $search = $request->get('search', '');
        $status = $request->get('status', '');

        // Get counts for all tabs
        $configuredCount = ErpEndpoint::where('is_auto_discovered', false)->count();
        $availableCount = ErpEndpoint::where('is_auto_discovered', true)
            ->where('is_active', false)
            ->count();
        $deprecatedCount = ErpEndpoint::whereNotNull('deprecated_at')->count();

        // Build base query with eager loading
        $baseQuery = ErpEndpoint::with(['credential', 'logs' => function ($q) {
            $q->latest()->limit(5);
        }]);

        // Filter by search
        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('url', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        // Filter by tab
        if ($tab === 'configured') {
            $query = $baseQuery->where('is_auto_discovered', false);

            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }

            $endpoints = $query->latest()->paginate(15);
            $availableEndpoints = null;
            $deprecatedEndpoints = null;
        } elseif ($tab === 'available') {
            $availableEndpoints = $baseQuery->where('is_auto_discovered', true)
                ->where('is_active', false)
                ->where(function ($q) {
                    $q->whereNull('deprecated_at');
                })
                ->latest()
                ->paginate(15);
            $endpoints = ErpEndpoint::where('is_auto_discovered', false)
                ->where('is_active', false)
                ->latest()
                ->paginate(0);
            $deprecatedEndpoints = null;
        } else { // deprecated
            $deprecatedEndpoints = $baseQuery->whereNotNull('deprecated_at')
                ->latest('deprecated_at')
                ->paginate(15);
            $endpoints = ErpEndpoint::where('is_auto_discovered', false)
                ->where('is_active', false)
                ->latest()
                ->paginate(0);
            $availableEndpoints = null;
        }

        return view('erp::settings.endpoints.index', compact(
            'endpoints',
            'availableEndpoints',
            'deprecatedEndpoints',
            'tab',
            'search',
            'status',
            'configuredCount',
            'availableCount',
            'deprecatedCount'
        ));
    }

    /**
     * Show the form for creating a new endpoint
     */
    public function create()
    {
        $credentials = ErpCredential::query()
            ->forAccount(auth()->user()->account_id ?? null)
            ->active()
            ->get();

        return view('erp::settings.endpoints.create', compact('credentials'));
    }

    /**
     * Store a newly created endpoint
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:erp_endpoints,slug',
            'url' => 'required|url|max:500',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'credential_id' => 'nullable|exists:erp_credentials,id',
            'description' => 'nullable|string',
            'timeout' => 'nullable|integer|min:1|max:300',
            'retry_attempts' => 'nullable|integer|min:0|max:10',
            'content_type' => 'nullable|string|max:100',
            'rate_limit' => 'nullable|integer|min:1',
            'headers' => 'nullable|array',
            'query_params' => 'nullable|array',
        ]);

        $endpoint = ErpEndpoint::create($validated);

        return redirect()->route('settings.erp.endpoints.show', $endpoint)
            ->with('success', 'Endpoint creado correctamente');
    }

    /**
     * Display the specified endpoint
     */
    public function show(ErpEndpoint $endpoint)
    {
        $endpoint->load(['credential', 'tokens', 'logs' => function ($q) {
            $q->latest();
        }]);

        // Get recent logs with pagination
        $logs = $endpoint->logs()
            ->latest()
            ->paginate(20);

        return view('erp::settings.endpoints.show', compact('endpoint', 'logs'));
    }

    /**
     * Show the form for editing the specified endpoint
     */
    public function edit(ErpEndpoint $endpoint)
    {
        $credentials = ErpCredential::query()
            ->forAccount(auth()->user()->account_id ?? null)
            ->active()
            ->get();

        return view('erp::settings.endpoints.edit', compact('endpoint', 'credentials'));
    }

    /**
     * Update the specified endpoint
     */
    public function update(Request $request, ErpEndpoint $endpoint)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:erp_endpoints,slug,'.$endpoint->id,
            'url' => 'required|url|max:500',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'credential_id' => 'nullable|exists:erp_credentials,id',
            'description' => 'nullable|string',
            'timeout' => 'nullable|integer|min:1|max:300',
            'retry_attempts' => 'nullable|integer|min:0|max:10',
            'content_type' => 'nullable|string|max:100',
            'rate_limit' => 'nullable|integer|min:1',
            'headers' => 'nullable|array',
            'query_params' => 'nullable|array',
        ]);

        $endpoint->update($validated);

        return redirect()->route('settings.erp.endpoints.show', $endpoint)
            ->with('success', 'Endpoint actualizado correctamente');
    }

    /**
     * Remove the specified endpoint
     */
    public function destroy(ErpEndpoint $endpoint)
    {
        $endpoint->delete();

        return redirect()->route('settings.erp.endpoints.index')
            ->with('success', 'Endpoint eliminado correctamente');
    }

    /**
     * Toggle endpoint active status
     */
    public function toggle(ErpEndpoint $endpoint)
    {
        $endpoint->update(['is_active' => ! $endpoint->is_active]);

        $status = $endpoint->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Endpoint {$status} correctamente");
    }

    /**
     * Test endpoint connection
     */
    public function test(ErpEndpoint $endpoint)
    {
        // Defensa en profundidad: valida también aquí, no solo al guardar —
        // cubre endpoints creados antes de este guard y cualquier caso donde
        // full_url difiera del campo url ya validado.
        if (! ErpEndpointUrlGuard::isAllowed($endpoint->full_url)) {
            return back()->with('error', 'La URL de este endpoint no está permitida (localhost, metadata de nube, o esquema no soportado).');
        }

        $startTime = microtime(true);

        try {
            // Get credential
            $credential = $endpoint->credential;

            // Build request
            $http = Http::timeout($endpoint->timeout)
                ->withHeaders(array_merge(
                    $endpoint->headers ?? [],
                    $credential ? $credential->getAuthHeaders() : []
                ));

            // Add content type
            if ($endpoint->content_type) {
                $http->withHeaders(['Content-Type' => $endpoint->content_type]);
            }

            // Make request
            $response = match ($endpoint->method) {
                'GET' => $http->get($endpoint->full_url),
                'POST' => $http->post($endpoint->full_url, []),
                'PUT' => $http->put($endpoint->full_url, []),
                'PATCH' => $http->patch($endpoint->full_url, []),
                'DELETE' => $http->delete($endpoint->full_url),
                default => throw new \Exception('Método HTTP no soportado: '.$endpoint->method),
            };

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            // Log the request
            ErpEndpointLog::create([
                'endpoint_id' => $endpoint->id,
                'user_id' => auth()->id(),
                'method' => $endpoint->method,
                'url' => $endpoint->full_url,
                'request_headers' => $endpoint->headers,
                'request_payload' => [],
                'response_headers' => $response->headers(),
                'response_payload' => $response->json(),
                'status_code' => $response->status(),
                'execution_time' => $executionTime,
                'success' => $response->successful(),
                'error_message' => $response->failed() ? $response->body() : null,
                'ip_address' => request()->ip(),
            ]);

            if ($response->successful()) {
                return back()->with('success', "Conexión exitosa! Status: {$response->status()}, Tiempo: {$executionTime}ms");
            }

            return back()->with('error', "Error en conexión. Status: {$response->status()}");

        } catch (\Exception $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            // Log the error
            ErpEndpointLog::create([
                'endpoint_id' => $endpoint->id,
                'user_id' => auth()->id(),
                'method' => $endpoint->method,
                'url' => $endpoint->full_url,
                'request_headers' => $endpoint->headers,
                'execution_time' => $executionTime,
                'success' => false,
                'error_message' => $e->getMessage(),
                'ip_address' => request()->ip(),
            ]);

            Log::error('Error testing ERP endpoint', [
                'endpoint_id' => $endpoint->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al probar conexión: '.$e->getMessage());
        }
    }

    /**
     * Clear logs for an endpoint
     */
    public function clearLogs(ErpEndpoint $endpoint)
    {
        $count = $endpoint->logs()->delete();

        return back()->with('success', "{$count} logs eliminados correctamente");
    }

    /**
     * Generate a public token for an endpoint
     */
    public function generateToken(Request $request, ErpEndpoint $endpoint)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
            'usage_limit' => 'nullable|integer|min:1|max:1000000',
            'allowed_ips' => 'nullable|json',
        ]);

        $token = ErpEndpointToken::create(array_merge(
            $validated,
            [
                'endpoint_id' => $endpoint->id,
                'allowed_ips' => $validated['allowed_ips'] ? json_decode($validated['allowed_ips'], true) : null,
            ]
        ));

        return redirect()->route('settings.erp.endpoints.show', $endpoint)
            ->with('success', 'Token generado correctamente');
    }

    /**
     * Revoke a token
     */
    public function revokeToken(ErpEndpointToken $token)
    {
        $endpoint = $token->endpoint;
        $token->update(['is_active' => false]);

        return back()->with('success', 'Token revocado correctamente');
    }

    /**
     * Show token generation form
     */
    public function showTokenForm(ErpEndpoint $endpoint)
    {
        return view('erp::settings.endpoints.token-form', compact('endpoint'));
    }

    /**
     * Copy token to clipboard (return token value)
     */
    public function getTokenValue(ErpEndpointToken $token)
    {
        // Only return token if user owns it and can access it
        if ($token->endpoint->exists) {
            return response()->json([
                'token' => $token->token,
                'url' => route('api.erp.public', ['token' => $token->token, 'slug' => $token->endpoint->slug]),
            ]);
        }

        return response()->json(['error' => 'Token not found'], 404);
    }
}
