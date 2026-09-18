<?php

namespace Modules\Erp\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Erp\Models\ErpCredential;

class ErpCredentialsController extends Controller
{
    /**
     * Display a listing of all credentials (optionally filtered by endpoint)
     */
    public function index(Request $request, $endpoint = null)
    {
        $query = ErpCredential::query()
            ->forAccount(auth()->user()->account_id ?? null)
            ->withCount('endpoints')
            ->latest();

        // Filter by auth type
        if ($request->filled('auth_type')) {
            $query->where('auth_type', $request->auth_type);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $credentials = $query->paginate(15);

        return view('erp::settings.credentials.index', compact('credentials', 'endpoint'));
    }

    /**
     * Show the form for creating a new credential
     */
    public function create($endpoint = null)
    {
        return view('erp::settings.credentials.create', compact('endpoint'));
    }

    /**
     * Store a newly created credential
     */
    public function store(Request $request, $endpoint = null)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'auth_type' => 'required|in:none,basic,bearer,api_key,custom',
            'username' => 'required_if:auth_type,basic|nullable|string|max:255',
            'password' => 'required_if:auth_type,basic|nullable|string',
            'token' => 'required_if:auth_type,bearer|nullable|string',
            'api_key' => 'required_if:auth_type,api_key|nullable|string',
            'api_key_header' => 'nullable|string|max:100',
            'custom_headers' => 'required_if:auth_type,custom|nullable|array',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean',
        ]);

        $validated['account_id'] = auth()->user()->account_id ?? null;
        $validated['is_active'] = $request->boolean('is_active', true);

        // Set default api_key_header if not provided
        if ($validated['auth_type'] === 'api_key' && empty($validated['api_key_header'])) {
            $validated['api_key_header'] = 'X-API-Key';
        }

        ErpCredential::create($validated);

        // Redirect to appropriate location based on endpoint context
        $routeName = $endpoint ? 'settings.erp.endpoints.credentials.index' : 'settings.erp.credentials.index';
        $routeParams = $endpoint ? [$endpoint] : [];

        return redirect()->route($routeName, $routeParams)
            ->with('success', 'Credencial creada correctamente');
    }

    /**
     * Display the specified credential
     */
    public function show(ErpCredential $credential, $endpoint = null)
    {
        // Load endpoints using this credential
        $credential->load(['endpoints' => function ($query) {
            $query->latest();
        }]);

        return view('erp::settings.credentials.show', compact('credential', 'endpoint'));
    }

    /**
     * Show the form for editing the specified credential
     */
    public function edit(ErpCredential $credential, $endpoint = null)
    {
        return view('erp::settings.credentials.edit', compact('credential', 'endpoint'));
    }

    /**
     * Update the specified credential
     */
    public function update(Request $request, ErpCredential $credential, $endpoint = null)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'auth_type' => 'required|in:none,basic,bearer,api_key,custom',
            'username' => 'required_if:auth_type,basic|nullable|string|max:255',
            'password' => 'nullable|string',
            'token' => 'nullable|string',
            'api_key' => 'nullable|string',
            'api_key_header' => 'nullable|string|max:100',
            'custom_headers' => 'nullable|array',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        // Only update password/token/api_key if provided (they are encrypted)
        if (! $request->filled('password')) {
            unset($validated['password']);
        }
        if (! $request->filled('token')) {
            unset($validated['token']);
        }
        if (! $request->filled('api_key')) {
            unset($validated['api_key']);
        }

        // Set default api_key_header if auth_type is api_key and header is empty
        if ($validated['auth_type'] === 'api_key' && empty($validated['api_key_header'])) {
            $validated['api_key_header'] = 'X-API-Key';
        }

        $credential->update($validated);

        // Redirect to appropriate location based on endpoint context
        $routeName = $endpoint ? 'settings.erp.endpoints.credentials.index' : 'settings.erp.credentials.index';
        $routeParams = $endpoint ? [$endpoint] : [];

        return redirect()->route($routeName, $routeParams)
            ->with('success', 'Credencial actualizada correctamente');
    }

    /**
     * Remove the specified credential
     */
    public function destroy(ErpCredential $credential, $endpoint = null)
    {
        // Check if any endpoints are using this credential
        $endpointsCount = $credential->endpoints()->count();

        if ($endpointsCount > 0) {
            return back()->with('error', "No se puede eliminar. Esta credencial está siendo usada por {$endpointsCount} endpoint(s).");
        }

        $credential->delete();

        return back()->with('success', 'Credencial eliminada correctamente');
    }

    /**
     * Toggle credential active status
     */
    public function toggle(ErpCredential $credential, $endpoint = null)
    {
        $credential->update(['is_active' => ! $credential->is_active]);

        $status = $credential->is_active ? 'activada' : 'desactivada';

        return back()->with('success', "Credencial {$status} correctamente");
    }

    /**
     * Test credential validity
     */
    public function test(ErpCredential $credential, $endpoint = null)
    {
        try {
            // Check if credential is active
            if (! $credential->is_active) {
                return back()->with('error', 'La credencial está desactivada');
            }

            // Check if credential is expired
            if ($credential->isExpired()) {
                return back()->with('error', 'La credencial ha expirado');
            }

            // Validate auth type has required fields
            $valid = match ($credential->auth_type) {
                'basic' => ! empty($credential->username) && ! empty($credential->password),
                'bearer' => ! empty($credential->token),
                'api_key' => ! empty($credential->api_key),
                'custom' => ! empty($credential->custom_headers),
                'none' => true,
                default => false,
            };

            if (! $valid) {
                return back()->with('error', 'La credencial no tiene los campos requeridos para el tipo de autenticación');
            }

            // Mark as used
            $credential->markAsUsed();

            return back()->with('success', 'Credencial válida y lista para usar');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al validar credencial: '.$e->getMessage());
        }
    }

    /**
     * Rotate (regenerate) credential secrets
     */
    public function rotate(ErpCredential $credential, $endpoint = null)
    {
        try {
            $updates = [];

            // Regenerate secrets based on auth type
            match ($credential->auth_type) {
                'bearer' => $updates['token'] = bin2hex(random_bytes(32)),
                'api_key' => $updates['api_key'] = bin2hex(random_bytes(32)),
                'basic' => $updates['password'] = bin2hex(random_bytes(16)),
                'custom' => $updates['custom_headers'] = $credential->custom_headers, // Re-save to re-encrypt
                default => null,
            };

            if (! empty($updates)) {
                $credential->update($updates);

                return back()->with('success', 'Credencial rotada correctamente');
            }

            return back()->with('info', 'Esta credencial no requiere rotación (tipo: '.$credential->auth_type.')');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al rotar credencial: '.$e->getMessage());
        }
    }
}
