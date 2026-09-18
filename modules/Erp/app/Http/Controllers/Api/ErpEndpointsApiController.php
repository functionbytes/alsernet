<?php

namespace Modules\Erp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Erp\Http\Requests\StoreErpEndpointRequest;
use Modules\Erp\Http\Requests\UpdateErpEndpointRequest;
use Modules\Erp\Http\Resources\ErpEndpointLogResource;
use Modules\Erp\Http\Resources\ErpEndpointResource;
use Modules\Erp\Models\ErpEndpoint;
use Modules\Erp\Support\ErpEndpointUrlGuard;

class ErpEndpointsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ErpEndpoint::query()
            ->withCount(['logs', 'credentials'])
            ->with('credential');

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)
                ->orWhere('url', 'like', $term)
                ->orWhere('slug', 'like', $term));
        }

        if ($request->filled('method')) {
            $query->where('method', strtoupper((string) $request->string('method')));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));
        $endpoints = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => ErpEndpointResource::collection($endpoints->items()),
            'meta' => [
                'current_page' => $endpoints->currentPage(),
                'last_page' => $endpoints->lastPage(),
                'per_page' => $endpoints->perPage(),
                'total' => $endpoints->total(),
            ],
        ]);
    }

    public function store(StoreErpEndpointRequest $request): JsonResponse
    {
        $endpoint = ErpEndpoint::create($request->validated());

        return response()->json([
            'message' => 'Endpoint creado correctamente',
            'data' => new ErpEndpointResource($endpoint),
        ], 201);
    }

    public function show(ErpEndpoint $endpoint): JsonResponse
    {
        $endpoint->loadCount('logs')->load('credential');

        return response()->json([
            'data' => new ErpEndpointResource($endpoint),
            'statistics' => $this->buildStatistics($endpoint),
        ]);
    }

    public function update(UpdateErpEndpointRequest $request, ErpEndpoint $endpoint): JsonResponse
    {
        $endpoint->update($request->validated());

        return response()->json([
            'message' => 'Endpoint actualizado correctamente',
            'data' => new ErpEndpointResource($endpoint->fresh()),
        ]);
    }

    public function destroy(ErpEndpoint $endpoint): JsonResponse
    {
        $endpoint->delete();

        return response()->json([
            'message' => 'Endpoint eliminado correctamente',
        ]);
    }

    public function toggle(ErpEndpoint $endpoint): JsonResponse
    {
        $endpoint->is_active = ! $endpoint->is_active;
        $endpoint->save();

        return response()->json([
            'message' => $endpoint->is_active ? 'Endpoint activado' : 'Endpoint desactivado',
            'data' => new ErpEndpointResource($endpoint),
        ]);
    }

    public function test(ErpEndpoint $endpoint): JsonResponse
    {
        // Defensa en profundidad: valida también aquí, no solo al guardar —
        // cubre endpoints creados antes de este guard.
        if (! ErpEndpointUrlGuard::isAllowed($endpoint->url)) {
            return response()->json([
                'message' => 'La URL de este endpoint no está permitida (localhost, metadata de nube, o esquema no soportado).',
            ], 422);
        }

        $start = microtime(true);
        $statusCode = null;
        $success = false;
        $error = null;
        $bodyPreview = null;

        try {
            $client = Http::timeout((int) ($endpoint->timeout ?: 30));

            if (! empty($endpoint->headers) && is_array($endpoint->headers)) {
                $client = $client->withHeaders($endpoint->headers);
            }

            $response = $client->send(
                strtoupper($endpoint->method ?: 'GET'),
                $endpoint->url,
                ['query' => is_array($endpoint->query_params) ? $endpoint->query_params : []],
            );

            $statusCode = $response->status();
            $success = $response->successful();
            $bodyPreview = substr($response->body(), 0, 500);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $executionTime = (int) round((microtime(true) - $start) * 1000);

        // Persist this test call as a log row so dashboards can pick it up.
        $endpoint->logs()->create([
            'method' => strtoupper($endpoint->method ?: 'GET'),
            'url' => $endpoint->url,
            'status_code' => $statusCode,
            'execution_time' => $executionTime,
            'success' => $success,
            'error_message' => $error,
            'ip_address' => request()->ip(),
        ]);

        return response()->json([
            'success' => $success,
            'status_code' => $statusCode,
            'execution_time' => $executionTime,
            'body_preview' => $bodyPreview,
            'error' => $error,
        ]);
    }

    public function logs(Request $request, ErpEndpoint $endpoint): JsonResponse
    {
        $perPage = min(200, max(1, (int) $request->integer('per_page', 50)));

        $logs = $endpoint->logs()
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'endpoint' => [
                'id' => $endpoint->id,
                'name' => $endpoint->name,
            ],
            'data' => ErpEndpointLogResource::collection($logs->items()),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function clearLogs(ErpEndpoint $endpoint): JsonResponse
    {
        $deleted = $endpoint->logs()->delete();

        return response()->json([
            'message' => "Se eliminaron {$deleted} registro(s) de log",
            'deleted' => $deleted,
        ]);
    }

    public function statistics(ErpEndpoint $endpoint): JsonResponse
    {
        $endpoint->loadCount('logs');

        return response()->json([
            'endpoint' => [
                'id' => $endpoint->id,
                'name' => $endpoint->name,
            ],
            'statistics' => $this->buildStatistics($endpoint),
        ]);
    }

    /**
     * Single aggregate-query stats — replaces N separate COUNT/AVG/MAX calls.
     */
    private function buildStatistics(ErpEndpoint $endpoint): array
    {
        $row = $endpoint->logs()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_count')
            ->selectRaw('AVG(execution_time) as avg_time')
            ->selectRaw('MAX(execution_time) as max_time')
            ->first();

        $total = (int) ($row->total ?? 0);
        $successCount = (int) ($row->success_count ?? 0);

        return [
            'total_calls' => $total,
            'success_count' => $successCount,
            'failed_count' => $total - $successCount,
            'success_rate' => $total > 0 ? round(($successCount / $total) * 100, 2) : 0,
            'average_execution_time' => round((float) ($row->avg_time ?? 0), 2),
            'max_execution_time' => (float) ($row->max_time ?? 0),
        ];
    }
}
