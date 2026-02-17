<?php

namespace Modules\Mailrelay\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Http\Resources\V1\MailProviderResource;
use Modules\Mailrelay\Services\ProviderManager;

class MailProviderApiController extends Controller
{
    protected ProviderManager $providerManager;

    public function __construct(ProviderManager $providerManager)
    {
        $this->providerManager = $providerManager;
    }

    /**
     * Display a listing of mail providers.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MailProvider::class);

        $query = MailProvider::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by driver
        if ($request->filled('driver')) {
            $query->where('driver', $request->driver);
        }

        // Order by priority and default
        $query->orderBy('is_default', 'desc')
            ->orderBy('priority', 'desc')
            ->orderBy('name');

        $providers = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => MailProviderResource::collection($providers),
            'meta' => [
                'current_page' => $providers->currentPage(),
                'per_page' => $providers->perPage(),
                'total' => $providers->total(),
                'last_page' => $providers->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created mail provider.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MailProvider::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|string|in:mailrelay,mailtrap,sendgrid,aws_ses,postmark',
            'credentials' => 'required|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer|min:0|max:100',
            'limits' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            // Test credentials before saving
            $validationResult = $this->providerManager->validateCredentials(
                $validated['driver'],
                $validated['credentials']
            );

            $provider = MailProvider::create([
                'name' => $validated['name'],
                'driver' => $validated['driver'],
                'credentials' => $validated['credentials'],
                'is_active' => $validated['is_active'] ?? true,
                'is_default' => $validated['is_default'] ?? false,
                'priority' => $validated['priority'] ?? 0,
                'limits' => $validated['limits'] ?? null,
                'metadata' => $validated['metadata'] ?? null,
                'connection_ok' => $validationResult['valid'],
                'last_tested_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mail provider created successfully',
                'data' => new MailProviderResource($provider),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create mail provider',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified mail provider.
     */
    public function show(int $id): JsonResponse
    {
        $provider = MailProvider::findOrFail($id);

        $this->authorize('view', $provider);

        return response()->json([
            'success' => true,
            'data' => new MailProviderResource($provider),
        ]);
    }

    /**
     * Update the specified mail provider.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $provider = MailProvider::findOrFail($id);

        $this->authorize('update', $provider);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'credentials' => 'required|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer|min:0|max:100',
            'limits' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        try {
            $provider->update([
                'name' => $validated['name'],
                'credentials' => $validated['credentials'],
                'is_active' => $validated['is_active'] ?? $provider->is_active,
                'is_default' => $validated['is_default'] ?? $provider->is_default,
                'priority' => $validated['priority'] ?? $provider->priority,
                'limits' => $validated['limits'] ?? $provider->limits,
                'metadata' => $validated['metadata'] ?? $provider->metadata,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mail provider updated successfully',
                'data' => new MailProviderResource($provider->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update mail provider',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified mail provider.
     */
    public function destroy(int $id): JsonResponse
    {
        $provider = MailProvider::findOrFail($id);

        $this->authorize('delete', $provider);

        try {
            $provider->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mail provider deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete mail provider',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test the mail provider connection.
     */
    public function test(int $id): JsonResponse
    {
        $provider = MailProvider::findOrFail($id);

        $this->authorize('test', $provider);

        try {
            $result = $this->providerManager->testProvider($id);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set provider as default.
     */
    public function setDefault(int $id): JsonResponse
    {
        $provider = MailProvider::findOrFail($id);

        $this->authorize('setDefault', $provider);

        try {
            // Deactivate current default
            MailProvider::where('is_default', true)->update(['is_default' => false]);

            // Set new default
            $provider->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Provider set as default successfully',
                'data' => new MailProviderResource($provider->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set provider as default',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Toggle provider active status.
     */
    public function toggleActive(int $id): JsonResponse
    {
        $provider = MailProvider::findOrFail($id);

        $this->authorize('toggleActive', $provider);

        try {
            $provider->update(['is_active' => ! $provider->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Provider status toggled successfully',
                'data' => new MailProviderResource($provider->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle provider status',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get available drivers.
     */
    public function availableDrivers(): JsonResponse
    {
        $drivers = $this->providerManager->availableDrivers();

        $driversInfo = collect($drivers)->map(function ($driver) {
            return [
                'driver' => $driver,
                'info' => $this->providerManager->getDriverInfo($driver),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $driversInfo,
        ]);
    }
}
