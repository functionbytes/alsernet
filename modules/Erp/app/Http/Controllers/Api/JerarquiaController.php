<?php

namespace Modules\Erp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Http\Resources\FamiliaClResource;
use Modules\Erp\Http\Resources\GrupoClResource;
use Modules\Erp\Http\Resources\SubfamiliaClResource;
use Modules\Erp\Models\Oracle\Configuracion\FamiliaCl;
use Modules\Erp\Models\Oracle\Configuracion\SubfamiliaCl;
use Modules\Erp\Models\Oracle\Otros\GrupoCl;

/**
 * VERSIÓN ELOQUENT - GESTIÓN DE JERARQUÍA DE PRODUCTOS
 *
 * Endpoints:
 * - GET /api/erp/eloquent/familias - Listar familias
 * - GET /api/erp/eloquent/familias/{id} - Detalle familia con subfamilias
 * - GET /api/erp/eloquent/subfamilias - Listar subfamilias
 * - GET /api/erp/eloquent/subfamilias/{id} - Detalle subfamilia con grupos
 * - GET /api/erp/eloquent/grupos - Listar grupos
 * - GET /api/erp/eloquent/grupos/{id} - Detalle grupo con artículos
 */
class JerarquiaController extends Controller
{
    // ========================================
    // FAMILIAS
    // ========================================

    /**
     * Listar familias con paginación
     *
     * GET /api/erp/eloquent/familias?categoria_id=288
     */
    public function indexFamilias(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);

            $filters = $request->only(['descripcion', 'estado', 'categoria_id']);
            $result = FamiliaCl::fastPaginate($filters, $limit, $offset);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Familias: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error JerarquiaController@indexFamilias', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detalle de familia con subfamilias
     *
     * GET /api/erp/eloquent/familias/{id}
     */
    public function showFamilia(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $familia = FamiliaCl::with([
                'categoriaCl',
                'subfamilias' => function ($query) {
                    $query->whereNull('fbaja')
                        ->orderBy('descripcion', 'asc');
                },
            ])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Familia Detalle: '.round($totalTime * 1000, 2).'ms ===');

            $data = $this->cleanUtf8Array((new FamiliaClResource($familia))->resolve());

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Familia no encontrada',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error JerarquiaController@showFamilia', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ========================================
    // SUBFAMILIAS
    // ========================================

    /**
     * Listar subfamilias con paginación
     *
     * GET /api/erp/eloquent/subfamilias?familia_id=123
     */
    public function indexSubfamilias(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);

            $filters = $request->only(['descripcion', 'estado', 'familia_id']);
            $result = SubfamiliaCl::fastPaginate($filters, $limit, $offset);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Subfamilias: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error JerarquiaController@indexSubfamilias', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detalle de subfamilia con grupos
     *
     * GET /api/erp/eloquent/subfamilias/{id}
     */
    public function showSubfamilia(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $subfamilia = SubfamiliaCl::with([
                'familiaCl',
                'grupos' => function ($query) {
                    $query->whereNull('fbaja')
                        ->orderBy('descripcion', 'asc');
                },
            ])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Subfamilia Detalle: '.round($totalTime * 1000, 2).'ms ===');

            $data = $this->cleanUtf8Array((new SubfamiliaClResource($subfamilia))->resolve());

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Subfamilia no encontrada',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error JerarquiaController@showSubfamilia', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ========================================
    // GRUPOS
    // ========================================

    /**
     * Listar grupos con paginación
     *
     * GET /api/erp/eloquent/grupos?subfamilia_id=456
     */
    public function indexGrupos(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);

            $filters = $request->only(['descripcion', 'estado', 'subfamilia_id']);
            $result = GrupoCl::fastPaginate($filters, $limit, $offset);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Grupos: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error JerarquiaController@indexGrupos', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detalle de grupo con artículos
     *
     * GET /api/erp/eloquent/grupos/{id}
     */
    public function showGrupo(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $grupo = GrupoCl::with([
                'subfamiliaCl',
                'articulos' => function ($query) {
                    $query->whereNull('fbaja')
                        ->orderBy('codigo', 'asc')
                        ->limit(50); // Limitar a 50 artículos más recientes
                },
            ])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Grupo Detalle: '.round($totalTime * 1000, 2).'ms ===');

            $data = $this->cleanUtf8Array((new GrupoClResource($grupo))->resolve());

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Grupo no encontrado',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error JerarquiaController@showGrupo', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clean UTF-8 encoding recursively in arrays.
     */
    private function cleanUtf8Array($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'cleanUtf8Array'], $data);
        }

        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }

        return $data;
    }
}
