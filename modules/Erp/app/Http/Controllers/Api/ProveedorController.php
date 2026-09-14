<?php

namespace Modules\Erp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Http\Resources\ProveedorCategoriasResource;
use Modules\Erp\Http\Resources\ProveedorProductosResource;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;

/**
 * VERSIÓN ELOQUENT - GESTIÓN DE PROVEEDORES
 *
 * Endpoints:
 * - GET /api/erp/eloquent/proveedores - Listar proveedores
 * - GET /api/erp/eloquent/proveedores/{id}/productos - Proveedor con productos y categorías
 * - GET /api/erp/eloquent/proveedores/{id}/categorias - Proveedor con categorías agrupadas
 */
class ProveedorController extends Controller
{
    /**
     * Listar proveedores con paginación
     *
     * GET /api/erp/eloquent/proveedores
     */
    public function index(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);

            $filters = $request->only(['nombre', 'cif', 'estado']);
            $result = Proveedor::fastPaginate($filters, $limit, $offset);

            // Limpiar UTF-8
            if (isset($result['data']) && is_array($result['data'])) {
                $result['data'] = array_map([$this, 'cleanUtf8Array'], $result['data']);
            }

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Proveedores: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error ProveedorController@index', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Información básica del proveedor
     *
     * GET /api/erp/proveedores/{id}
     */
    public function show(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $proveedor = Proveedor::select([
                'idproveedor',
                'nombre',
                'cif',
                'percontacto',
                'telefono1',
                'telefono2',
                'fax',
                'email',
                'web',
                'calle',
                'num',
                'localidad',
                'cp',
                'provincia',
                'estado',
                'observaciones',
                'iban',
                'idtipoprov',
                'idpais',
                'idregfiscal',
                'fcreacion',
                'fmodificacion',
            ])
                ->with([
                    'tipoprov:idtipoprov,descripcion',
                    'pais:idpais,descripcion',
                    'regfiscal:idregfiscal,descripcion',
                ])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Proveedor Show: '.round($totalTime * 1000, 2).'ms ===');

            $data = $this->cleanUtf8Array([
                'id' => $proveedor->idproveedor,
                'nombre' => $proveedor->nombre,
                'cif' => $proveedor->cif,
                'percontacto' => $proveedor->percontacto,
                'telefono1' => $proveedor->telefono1,
                'telefono2' => $proveedor->telefono2,
                'fax' => $proveedor->fax,
                'email' => $proveedor->email,
                'web' => $proveedor->web,
                'direccion' => [
                    'calle' => $proveedor->calle,
                    'num' => $proveedor->num,
                    'localidad' => $proveedor->localidad,
                    'cp' => $proveedor->cp,
                    'provincia' => $proveedor->provincia,
                    'pais' => $proveedor->pais ? [
                        'id' => $proveedor->pais->idpais,
                        'descripcion' => $proveedor->pais->descripcion,
                    ] : null,
                ],
                'estado' => $proveedor->estado,
                'observaciones' => $proveedor->observaciones,
                'iban' => $proveedor->iban,
                'tipoprov' => $proveedor->tipoprov ? [
                    'id' => $proveedor->tipoprov->idtipoprov,
                    'descripcion' => $proveedor->tipoprov->descripcion,
                ] : null,
                'regfiscal' => $proveedor->regfiscal ? [
                    'id' => $proveedor->regfiscal->idregfiscal,
                    'descripcion' => $proveedor->regfiscal->descripcion,
                ] : null,
                'fechaCreacion' => $proveedor->fcreacion?->format('Y-m-d H:i:s'),
                'fechaModificacion' => $proveedor->fmodificacion?->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Proveedor no encontrado',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error ProveedorController@show', [
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
     * Información completa del proveedor: datos básicos + productos + categorías agrupadas
     *
     * GET /api/erp/proveedores/{id}/detallado
     */
    public function showDetallado(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $cacheKey = "proveedor:detallado:{$id}";

            $data = cache()->remember($cacheKey, 3600, function () use ($id) {
                $proveedor = Proveedor::select([
                    'idproveedor', 'nombre', 'cif', 'percontacto',
                    'telefono1', 'telefono2', 'fax', 'email', 'web',
                    'calle', 'num', 'localidad', 'cp', 'provincia',
                    'estado', 'observaciones', 'iban',
                    'idtipoprov', 'idpais', 'idregfiscal',
                    'fcreacion', 'fmodificacion',
                ])
                    ->with([
                        'tipoprov:idtipoprov,descripcion',
                        'pais:idpais,descripcion',
                        'regfiscal:idregfiscal,descripcion',
                        'artiprovs:idartiprov,idproveedor,idarticulo,codigo,descripcion,pcosto,pordefecto,estado',
                        'artiprovs.articulo:idarticulo,codigo,descripcion,idgrupo_cl',
                        'artiprovs.articulo.grupoCl:idgrupo_cl,idsubfamilia_cl',
                        'artiprovs.articulo.grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                        'artiprovs.articulo.grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl',
                        'artiprovs.articulo.grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,descripcion',
                    ])
                    ->whereNull('fbaja')
                    ->findOrFail($id);

                // Categorías únicas derivadas de los productos
                $categorias = $proveedor->artiprovs
                    ->map(fn ($a) => $a->articulo?->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl)
                    ->filter()
                    ->unique('idcategoria_cl')
                    ->values();

                return [
                    // Info básica
                    'id' => $proveedor->idproveedor,
                    'nombre' => $proveedor->nombre,
                    'cif' => $proveedor->cif,
                    'percontacto' => $proveedor->percontacto,
                    'telefono1' => $proveedor->telefono1,
                    'telefono2' => $proveedor->telefono2,
                    'fax' => $proveedor->fax,
                    'email' => $proveedor->email,
                    'web' => $proveedor->web,
                    'direccion' => [
                        'calle' => $proveedor->calle,
                        'num' => $proveedor->num,
                        'localidad' => $proveedor->localidad,
                        'cp' => $proveedor->cp,
                        'provincia' => $proveedor->provincia,
                        'pais' => $proveedor->pais ? [
                            'id' => $proveedor->pais->idpais,
                            'descripcion' => $proveedor->pais->descripcion,
                        ] : null,
                    ],
                    'estado' => $proveedor->estado,
                    'observaciones' => $proveedor->observaciones,
                    'iban' => $proveedor->iban,
                    'tipoprov' => $proveedor->tipoprov ? [
                        'id' => $proveedor->tipoprov->idtipoprov,
                        'descripcion' => $proveedor->tipoprov->descripcion,
                    ] : null,
                    'regfiscal' => $proveedor->regfiscal ? [
                        'id' => $proveedor->regfiscal->idregfiscal,
                        'descripcion' => $proveedor->regfiscal->descripcion,
                    ] : null,

                    // Productos
                    'productos' => $proveedor->artiprovs->map(fn ($artiprov) => [
                        'idArtiprov' => $artiprov->idartiprov,
                        'codigo' => $artiprov->codigo,
                        'descripcion' => $artiprov->descripcion,
                        'pcosto' => $artiprov->pcosto,
                        'porDefecto' => $artiprov->pordefecto,
                        'articulo' => $artiprov->articulo ? [
                            'id' => $artiprov->articulo->idarticulo,
                            'codigo' => $artiprov->articulo->codigo,
                            'descripcion' => $artiprov->articulo->descripcion,
                            'categoria' => $artiprov->articulo->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl ? [
                                'id' => $artiprov->articulo->grupoCl->subfamiliaCl->familiaCl->categoriaCl->idcategoria_cl,
                                'descripcion' => $artiprov->articulo->grupoCl->subfamiliaCl->familiaCl->categoriaCl->descripcion,
                            ] : null,
                        ] : null,
                    ])->values(),

                    // Categorías agrupadas
                    'categorias' => $categorias->map(fn ($cat) => [
                        'id' => $cat->idcategoria_cl,
                        'descripcion' => $cat->descripcion,
                        'totalProductos' => $proveedor->artiprovs->filter(
                            fn ($a) => $a->articulo?->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->idcategoria_cl === $cat->idcategoria_cl
                        )->count(),
                    ])->values(),

                    // Estadísticas
                    'estadisticas' => [
                        'totalProductos' => $proveedor->artiprovs->count(),
                        'productosActivos' => $proveedor->artiprovs->where('estado', 1)->count(),
                        'totalCategorias' => $categorias->count(),
                    ],

                    'fechaCreacion' => $proveedor->fcreacion?->format('Y-m-d H:i:s'),
                    'fechaModificacion' => $proveedor->fmodificacion?->format('Y-m-d H:i:s'),
                ];
            });

            $totalTime = microtime(true) - $startTime;
            $fromCache = cache()->has($cacheKey);

            Log::debug('=== TIEMPO Proveedor Detallado: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data),
                'meta' => [
                    'cached' => $fromCache,
                    'execution_time_ms' => round($totalTime * 1000, 2),
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Proveedor no encontrado',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error ProveedorController@showDetallado', [
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
     * Proveedor con sus productos y categorías de cada producto
     *
     * GET /api/erp/eloquent/proveedores/{id}/productos
     */
    public function showProductos(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Cache key
            $cacheKey = "proveedor:productos:{$id}";

            $data = cache()->remember($cacheKey, 3600, function () use ($id) {
                $proveedor = Proveedor::select([
                    'idproveedor',
                    'nombre',
                    'cif',
                    'email',
                    'telefono1',
                    'estado',
                    'fcreacion',
                    'fmodificacion',
                ])
                    ->with([
                        'artiprovs:idartiprov,idproveedor,idarticulo,codigo,descripcion,pcosto,pordefecto,estado',
                        'artiprovs.articulo:idarticulo,codigo,descripcion,idgrupo_cl',
                        'artiprovs.articulo.grupoCl:idgrupo_cl,idsubfamilia_cl',
                        'artiprovs.articulo.grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                        'artiprovs.articulo.grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl',
                        'artiprovs.articulo.grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,descripcion',
                    ])
                    ->whereNull('fbaja')
                    ->findOrFail($id);

                return (new ProveedorProductosResource($proveedor))->resolve();
            });

            $totalTime = microtime(true) - $startTime;
            $fromCache = cache()->has($cacheKey);

            Log::debug('=== TIEMPO Proveedor Productos: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

            $cleanData = $this->cleanUtf8Array($data);

            return response()->json([
                'success' => true,
                'data' => $cleanData,
                'meta' => [
                    'cached' => $fromCache,
                    'execution_time_ms' => round($totalTime * 1000, 2),
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Proveedor no encontrado',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error ProveedorController@showProductos', [
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
     * Proveedor con categorías agrupadas (qué categorías pertenecen por los productos)
     *
     * GET /api/erp/eloquent/proveedores/{id}/categorias
     */
    public function showCategorias(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Cache key
            $cacheKey = "proveedor:categorias:{$id}";

            $data = cache()->remember($cacheKey, 3600, function () use ($id) {
                $proveedor = Proveedor::select([
                    'idproveedor',
                    'nombre',
                    'cif',
                    'email',
                    'estado',
                    'fcreacion',
                    'fmodificacion',
                ])
                    ->with([
                        'artiprovs:idartiprov,idproveedor,idarticulo',
                        'artiprovs.articulo:idarticulo,idgrupo_cl',
                        'artiprovs.articulo.grupoCl:idgrupo_cl,idsubfamilia_cl',
                        'artiprovs.articulo.grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                        'artiprovs.articulo.grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl',
                        'artiprovs.articulo.grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,descripcion',
                    ])
                    ->whereNull('fbaja')
                    ->findOrFail($id);

                return (new ProveedorCategoriasResource($proveedor))->resolve();
            });

            $totalTime = microtime(true) - $startTime;
            $fromCache = cache()->has($cacheKey);

            Log::debug('=== TIEMPO Proveedor Categorías: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

            $cleanData = $this->cleanUtf8Array($data);

            return response()->json([
                'success' => true,
                'data' => $cleanData,
                'meta' => [
                    'cached' => $fromCache,
                    'execution_time_ms' => round($totalTime * 1000, 2),
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Proveedor no encontrado',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error ProveedorController@showCategorias', [
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
