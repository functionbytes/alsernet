<?php

namespace Modules\Erp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\Oracle\Web\WCaracteristicasOrden;
use Modules\Erp\Models\Oracle\Web\WCaracteristicasProd;
use Modules\Erp\Models\Oracle\Web\WPerfilesProd;
use Modules\Erp\Models\Oracle\Web\WValoresProd;

/**
 * GESTIÓN DE CARACTERÍSTICAS DE PRODUCTOS
 *
 * Endpoints:
 * - GET /api/erp/characteristics - Listar características
 * - GET /api/erp/characteristic-values - Listar valores de características
 * - GET /api/erp/model-characteristics - Listar características por modelo
 * - GET /api/erp/variant-characteristics - Listar características por variante
 *
 * Nota: usa Eloquent normal (conexión PDO 'oracle') en vez del helper
 * fastPaginate()/OCI8Service del trait UsesOCI8Performance — ese helper
 * necesita credenciales OCI8 crudas (ORACLE_USERNAME/ORACLE_PASSWORD) que
 * no están configuradas en todos los entornos, mientras que la conexión
 * Eloquent estándar sí funciona en todos ellos.
 */
class CaracteristicasController extends Controller
{
    /**
     * Pagina un query Eloquent con el mismo contrato {success, data, pagination}
     * que fastPaginate(), pidiendo limit+1 filas para deducir "hasMore" sin COUNT(*).
     */
    private function paginate(Builder $query, array $filters, int $limit, int $offset): array
    {
        foreach ($filters as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_string($value) && str_contains($value, '%')) {
                $query->whereRaw("UPPER({$column}) LIKE UPPER(?)", [$value]);
            } else {
                $query->where($column, $value);
            }
        }

        $rows = $query->skip($offset)->take($limit + 1)->get();
        $hasMore = $rows->count() > $limit;

        if ($hasMore) {
            $rows = $rows->slice(0, $limit)->values();
        }

        return [
            'success' => true,
            'data' => $rows->toArray(),
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => $rows->count(),
                'hasMore' => $hasMore,
            ],
        ];
    }

    /**
     * Listar características con paginación
     *
     * GET /api/erp/characteristics?nombre=Color
     */
    public function indexCaracteristicas(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 1000);
            $offset = (int) $request->get('offset', 0);

            $filters = $request->only(['nombre', 'estado']);
            $result = $this->paginate(WCaracteristicasProd::query(), $filters, $limit, $offset);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Caracteristicas: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error CaracteristicasController@indexCaracteristicas', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar valores de características con paginación
     *
     * GET /api/erp/characteristic-values?id_caracteristica=123
     */
    public function indexValores(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 1000);
            $offset = (int) $request->get('offset', 0);

            $filters = $request->only(['id_caracteristica', 'nombre', 'estado']);
            $result = $this->paginate(WValoresProd::query(), $filters, $limit, $offset);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Valores: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error CaracteristicasController@indexValores', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar características por modelo con paginación
     *
     * GET /api/erp/model-characteristics?idmodelo=456
     */
    public function indexModeloCaracteristicas(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 1000);
            $offset = (int) $request->get('offset', 0);

            $filters = $request->only(['idmodelo', 'id_caracteristica', 'estado']);
            $result = $this->paginate(WCaracteristicasOrden::query(), $filters, $limit, $offset);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO ModeloCaracteristicas: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error CaracteristicasController@indexModeloCaracteristicas', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar características por variante con paginación
     *
     * GET /api/erp/variant-characteristics?idarticulo=789
     */
    public function indexVarianteCaracteristicas(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 1000);
            $offset = (int) $request->get('offset', 0);

            $filters = $request->only(['idarticulo', 'idmodelo', 'id_valor', 'estado']);
            $result = $this->paginate(WPerfilesProd::query(), $filters, $limit, $offset);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO VarianteCaracteristicas: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error CaracteristicasController@indexVarianteCaracteristicas', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
