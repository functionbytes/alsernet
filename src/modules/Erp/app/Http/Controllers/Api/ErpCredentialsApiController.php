<?php

namespace Modules\Erp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Erp\Http\Requests\StoreErpCredentialRequest;
use Modules\Erp\Http\Requests\UpdateErpCredentialRequest;
use Modules\Erp\Http\Resources\ErpCredentialResource;
use Modules\Erp\Http\Resources\ErpEndpointResource;
use Modules\Erp\Models\ErpCredential;
use Modules\Erp\Models\ErpEndpoint;

class ErpCredentialsApiController extends Controller
{
    /**
     * Display a listing of credentials for an endpoint.
     */
    public function index(string $endpointId): JsonResponse
    {
        $endpoint = ErpEndpoint::findOrFail($endpointId);
        $credentials = $endpoint->credentials()->latest()->get();

        return response()->json([
            'endpoint' => new ErpEndpointResource($endpoint),
            'data' => ErpCredentialResource::collection($credentials),
        ]);
    }

    /**
     * Store a newly created credential.
     */
    public function store(StoreErpCredentialRequest $request, string $endpointId): JsonResponse
    {
        $endpoint = ErpEndpoint::findOrFail($endpointId);
        $validated = $request->validated();

        // Deactivate other credentials if this one is active
        if ($request->boolean('is_active', true)) {
            $endpoint->credentials()->update(['is_active' => false]);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        // Crear vía la relación: fija endpoint_id (que no está en $fillable, así
        // que ErpCredential::create() lo descartaba dejando la credencial huérfana).
        $credential = $endpoint->credentials()->create($validated);

        return response()->json([
            'message' => 'Credencial creada correctamente',
            'data' => new ErpCredentialResource($credential),
        ], 201);
    }

    /**
     * Display the specified credential.
     */
    public function show(string $endpointId, string $credentialId): JsonResponse
    {
        $endpoint = ErpEndpoint::findOrFail($endpointId);
        $credential = $endpoint->credentials()->findOrFail($credentialId);

        return response()->json([
            'data' => $credential,
        ]);
    }

    /**
     * Update the specified credential.
     */
    public function update(UpdateErpCredentialRequest $request, string $endpointId, string $credentialId): JsonResponse
    {
        $endpoint = ErpEndpoint::findOrFail($endpointId);
        $credential = $endpoint->credentials()->findOrFail($credentialId);
        $validated = $request->validated();

        // Deactivate other credentials if this one is being activated
        if ($request->boolean('is_active') && ! $credential->is_active) {
            $endpoint->credentials()->where('id', '!=', $credential->id)->update(['is_active' => false]);
        }

        // Only update password/token/api_key if provided
        if (! $request->filled('password')) {
            unset($validated['password']);
        }
        if (! $request->filled('token')) {
            unset($validated['token']);
        }
        if (! $request->filled('api_key')) {
            unset($validated['api_key']);
        }

        $credential->update($validated);

        return response()->json([
            'message' => 'Credencial actualizada correctamente',
            'data' => new ErpCredentialResource($credential),
        ]);
    }

    /**
     * Remove the specified credential.
     */
    public function destroy(string $endpointId, string $credentialId): JsonResponse
    {
        $endpoint = ErpEndpoint::findOrFail($endpointId);
        $credential = $endpoint->credentials()->findOrFail($credentialId);

        $credential->delete();

        return response()->json([
            'message' => 'Credencial eliminada correctamente',
        ]);
    }

    /**
     * Toggle credential active status.
     */
    public function toggle(string $endpointId, string $credentialId): JsonResponse
    {
        $endpoint = ErpEndpoint::findOrFail($endpointId);
        $credential = $endpoint->credentials()->findOrFail($credentialId);

        // If activating, deactivate others
        if (! $credential->is_active) {
            $endpoint->credentials()->where('id', '!=', $credential->id)->update(['is_active' => false]);
        }

        $credential->update(['is_active' => ! $credential->is_active]);

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'data' => $credential,
        ]);
    }

    /**
     * Rotate credential (create a new one).
     */
    public function rotate(string $endpointId, string $credentialId): JsonResponse
    {
        $endpoint = ErpEndpoint::findOrFail($endpointId);
        $credential = $endpoint->credentials()->findOrFail($credentialId);

        // Create a duplicate with new credentials
        $newCredential = $credential->replicate();
        $newCredential->is_active = false;
        $newCredential->expires_at = null;
        $newCredential->last_used_at = null;
        $newCredential->save();

        return response()->json([
            'message' => 'Nueva credencial creada. Actualice los valores y actívela cuando esté lista.',
            'data' => $newCredential,
        ], 201);
    }
}
