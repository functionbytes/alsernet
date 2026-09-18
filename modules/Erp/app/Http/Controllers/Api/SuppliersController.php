<?php

namespace Modules\Erp\Http\Controllers\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;

/**
 * VERSIÓN ELOQUENT - GESTIÓN DE PROVEEDORES
 *
 * Endpoints:
 * - GET /api/erp/eloquent/proveedores - Listar proveedores
 * - GET /api/erp/eloquent/proveedores/{id}/productos - Proveedor con productos y categorías
 * - GET /api/erp/eloquent/proveedores/{id}/categorias - Proveedor con categorías agrupadas
 */
class SuppliersController extends ApiController
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
                'label' => $proveedor->nombre,
                'cif' => $proveedor->cif,
                'email' => $proveedor->email,
                'available' => $proveedor->estado,
                'created' => $proveedor->fcreacion?->format('Y-m-d H:i:s'),
                'updated' => $proveedor->fmodificacion?->format('Y-m-d H:i:s'),
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
    public function showDetailed(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult("supplier:detailed:{$id}", function () use ($id) {
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
                        'artiprovs:idartiprov,idproveedor,idarticulo,codigo,codigo2,ean13,upc,descripcion,pcosto,pordefecto,estado',
                        'artiprovs.articulo:idarticulo,codigo,descripcion,idmodelo',
                        'artiprovs.articulo.modelo:idmodelo,codigo,nombre,estado_publicado_web',
                        'artiprovs.articulo.modelo.articulos:idarticulo,codigo,descripcion,referencia,ean_interno,idmodelo,idgrupo_cl,estado,estado_publicado_web,fcreacion,fmodificacion',
                        'artiprovs.articulo.modelo.articulos.grupoCl:idgrupo_cl,idsubfamilia_cl',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl,descripcion,desc_corta,estado,fcreacion,fmodificacion',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,iddeporte_cl,descripcion',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl.deporteCl:iddeporte_cl,descripcion,desc_corta,estado',
                    ])
                    ->whereNull('fbaja')
                    ->findOrFail($id);

                $familias = $proveedor->artiprovs
                    ->flatMap(fn ($ap) => $ap->articulo?->modelo?->articulos ?? collect())
                    ->map(fn ($a) => $a->grupoCl?->subfamiliaCl?->familiaCl)
                    ->filter()
                    ->unique('idfamilia_cl')
                    ->values();

                $deportes = $familias
                    ->map(fn ($f) => $f->categoriaCl?->deporteCl)
                    ->filter()
                    ->unique('iddeporte_cl')
                    ->values();

                $products = $this->mapProductsByModel($proveedor->artiprovs);

                return [
                    'id' => $proveedor->idproveedor,
                    'label' => $proveedor->nombre,
                    'cif' => $proveedor->cif,
                    'email' => $proveedor->email,
                    'available' => $proveedor->estado,
                    'sports' => $deportes->map(fn ($d) => [
                        'id' => $d->iddeporte_cl,
                        'description' => $d->descripcion,
                        'description_short' => $d->desc_corta,
                        'available' => $d->estado,
                    ])->values(),
                    'products' => $products,
                    'categories' => $familias->map(fn ($f) => [
                        'id' => $f->idfamilia_cl,
                        'description' => $f->descripcion,
                        'description_short' => $f->desc_corta,
                        'available' => $f->estado,
                        'created' => $f->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $f->fmodificacion?->format('Y-m-d H:i:s'),
                    ])->values(),
                    'statistics' => [
                        'product' => ['total' => $products->count()],
                        'categories' => ['total' => $familias->count()],
                        'sports' => ['total' => $deportes->count()],
                    ],
                    'created' => $proveedor->fcreacion?->format('Y-m-d H:i:s'),
                    'updated' => $proveedor->fmodificacion?->format('Y-m-d H:i:s'),
                ];
            });

            $totalTime = microtime(true) - $startTime;

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
            Log::error('Error ProveedorController@showDetailed', [
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
    public function showProducts(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $cacheKey = "supplier:products:{$id}";
            $fromCache = cache()->has($cacheKey);

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
                        'artiprovs:idartiprov,idproveedor,idarticulo,codigo,codigo2,ean13,upc,descripcion,pcosto,pordefecto,estado',
                        'artiprovs.articulo:idarticulo,codigo,descripcion,idmodelo',
                        'artiprovs.articulo.modelo:idmodelo,codigo,nombre,estado_publicado_web',
                        'artiprovs.articulo.modelo.articulos:idarticulo,codigo,descripcion,referencia,ean_interno,idmodelo,idgrupo_cl,estado,estado_publicado_web,fcreacion,fmodificacion',
                        'artiprovs.articulo.modelo.articulos.grupoCl:idgrupo_cl,idsubfamilia_cl',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl,descripcion,desc_corta,estado',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl,descripcion,desc_corta,estado,fcreacion,fmodificacion',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,iddeporte_cl,descripcion',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl.deporteCl:iddeporte_cl,descripcion,desc_corta,estado',
                    ])
                    ->whereNull('fbaja')
                    ->findOrFail($id);

                $articulos = $proveedor->artiprovs
                    ->flatMap(fn ($ap) => $ap->articulo?->modelo?->articulos ?? collect());

                $subfamilias = $articulos
                    ->map(fn ($a) => $a->grupoCl?->subfamiliaCl)
                    ->filter()
                    ->unique('idsubfamilia_cl')
                    ->values();

                $familias = $subfamilias
                    ->map(fn ($s) => $s->familiaCl)
                    ->filter()
                    ->unique('idfamilia_cl')
                    ->values();

                $deportes = $familias
                    ->map(fn ($f) => $f->categoriaCl?->deporteCl)
                    ->filter()
                    ->unique('iddeporte_cl')
                    ->values();

                $products = $this->mapProductsByModel($proveedor->artiprovs);

                return [
                    'id' => $proveedor->idproveedor,
                    'label' => $proveedor->nombre,
                    'cif' => $proveedor->cif,
                    'email' => $proveedor->email,
                    'available' => $proveedor->estado,
                    'sports' => $deportes->map(fn ($d) => [
                        'id' => $d->iddeporte_cl,
                        'description' => $d->descripcion,
                        'description_short' => $d->desc_corta,
                        'available' => $d->estado,
                    ])->values(),
                    'categories' => $familias->map(fn ($f) => [
                        'id'               => $f->idfamilia_cl,
                        'description'      => $f->descripcion,
                        'description_short'=> $f->desc_corta,
                        'available'        => $f->estado,
                        'sport_id'         => $f->categoriaCl?->iddeporte_cl,
                        'categoria_id'     => $f->categoriaCl?->idcategoria_cl,
                        'categoria_name'   => $f->categoriaCl?->descripcion,
                        'categoria_id'     => $f->categoriaCl?->idcategoria_cl,
                        'categoria_name'   => $f->categoriaCl?->descripcion,
                        'created'          => $f->fcreacion?->format('Y-m-d H:i:s'),
                        'updated'          => $f->fmodificacion?->format('Y-m-d H:i:s'),
                    ])->values(),
                    'subfamilies' => $subfamilias->map(fn ($s) => [
                        'id' => $s->idsubfamilia_cl,
                        'description' => $s->descripcion,
                        'description_short' => $s->desc_corta,
                        'available' => $s->estado,
                        'family_id' => $s->idfamilia_cl,
                    ])->values(),
                    'products' => $products,
                    'statistics' => [
                        'product' => ['total' => $products->count()],
                        'categories' => ['total' => $familias->count()],
                        'subfamilies' => ['total' => $subfamilias->count()],
                        'sports' => ['total' => $deportes->count()],
                    ],
                    'created' => $proveedor->fcreacion?->format('Y-m-d H:i:s'),
                    'updated' => $proveedor->fmodificacion?->format('Y-m-d H:i:s'),
                ];
            });

            $totalTime = microtime(true) - $startTime;

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
            Log::error('Error ProveedorController@showProducts', [
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
    public function showCategories(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $cacheKey = "supplier:categories:{$id}";
            $fromCache = cache()->has($cacheKey);

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
                        'artiprovs.articulo:idarticulo,idmodelo',
                        'artiprovs.articulo.modelo:idmodelo',
                        'artiprovs.articulo.modelo.articulos:idarticulo,idmodelo,idgrupo_cl',
                        'artiprovs.articulo.modelo.articulos.grupoCl:idgrupo_cl,idsubfamilia_cl',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl,descripcion,desc_corta,estado,fcreacion,fmodificacion',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,iddeporte_cl,descripcion',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl.deporteCl:iddeporte_cl,descripcion,desc_corta,estado',
                    ])
                    ->whereNull('fbaja')
                    ->findOrFail($id);

                $familias = $proveedor->artiprovs
                    ->flatMap(fn ($ap) => $ap->articulo?->modelo?->articulos ?? collect())
                    ->map(fn ($a) => $a->grupoCl?->subfamiliaCl?->familiaCl)
                    ->filter()
                    ->unique('idfamilia_cl')
                    ->values();

                $deportes = $familias
                    ->map(fn ($f) => $f->categoriaCl?->deporteCl)
                    ->filter()
                    ->unique('iddeporte_cl')
                    ->values();

                return [
                    'id' => $proveedor->idproveedor,
                    'label' => $proveedor->nombre,
                    'cif' => $proveedor->cif,
                    'email' => $proveedor->email,
                    'available' => $proveedor->estado,
                    'created' => $proveedor->fcreacion?->format('Y-m-d H:i:s'),
                    'updated' => $proveedor->fmodificacion?->format('Y-m-d H:i:s'),
                    'sports' => $deportes->map(fn ($d) => [
                        'id' => $d->iddeporte_cl,
                        'description' => $d->descripcion,
                        'description_short' => $d->desc_corta,
                        'available' => $d->estado,
                    ])->values(),
                    'categories' => $familias->map(fn ($f) => [
                        'id' => $f->idfamilia_cl,
                        'description' => $f->descripcion,
                        'description_short' => $f->desc_corta,
                        'available' => $f->estado,
                        'created' => $f->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $f->fmodificacion?->format('Y-m-d H:i:s'),
                    ])->values(),
                    'statistics' => [
                        'categories' => ['total' => $familias->count()],
                        'sports' => ['total' => $deportes->count()],
                    ],
                ];
            });

            $totalTime = microtime(true) - $startTime;

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
            Log::error('Error ProveedorController@showCategories', [
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
    /**
     * Productos del proveedor sin descripción y con web = true
     *
     * GET /api/erp/suppliers/{id}/supplier
     */
    public function showSupplier(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult("supplier:supplier:{$id}", function () use ($id) {
                $proveedor = Proveedor::select([
                    'idproveedor', 'nombre', 'cif', 'email', 'estado', 'fcreacion', 'fmodificacion',
                ])
                    ->with([
                        'artiprovs:idartiprov,idproveedor,idarticulo,codigo,codigo2,ean13,upc,descripcion,pcosto,pordefecto,estado',
                        'artiprovs.articulo:idarticulo,codigo,descripcion,idmodelo,estado_publicado_web',
                        'artiprovs.articulo.modelo:idmodelo,codigo,nombre,descripcion,estado_publicado_web',
                        'artiprovs.articulo.modelo.articulos:idarticulo,codigo,descripcion,referencia,ean_interno,idmodelo,idgrupo_cl,estado,estado_publicado_web,fcreacion,fmodificacion',
                        'artiprovs.articulo.modelo.articulos.grupoCl:idgrupo_cl,idsubfamilia_cl',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl,descripcion,desc_corta,estado,fcreacion,fmodificacion',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,iddeporte_cl,descripcion',
                        'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl.deporteCl:iddeporte_cl,descripcion,desc_corta,estado',
                    ])
                    ->whereNull('fbaja')
                    ->findOrFail($id);

                // Filtrar: modelo sin descripción Y web=true
                $filtered = $proveedor->artiprovs->filter(
                    fn ($ap) => empty($ap->articulo?->modelo?->descripcion)
                        && $ap->articulo?->modelo?->estado_publicado_web === true
                )->values();

                $familias = $filtered
                    ->flatMap(fn ($ap) => $ap->articulo?->modelo?->articulos ?? collect())
                    ->map(fn ($a) => $a->grupoCl?->subfamiliaCl?->familiaCl)
                    ->filter()
                    ->unique('idfamilia_cl')
                    ->values();

                $deportes = $familias
                    ->map(fn ($f) => $f->categoriaCl?->deporteCl)
                    ->filter()
                    ->unique('iddeporte_cl')
                    ->values();

                $products = $this->mapProductsByModel($filtered);

                return [
                    'id' => $proveedor->idproveedor,
                    'label' => $proveedor->nombre,
                    'cif' => $proveedor->cif,
                    'email' => $proveedor->email,
                    'available' => $proveedor->estado,
                    'sports' => $deportes->map(fn ($d) => [
                        'id' => $d->iddeporte_cl,
                        'description' => $d->descripcion,
                        'description_short' => $d->desc_corta,
                        'available' => $d->estado,
                    ])->values(),
                    'products' => $products,
                    'categories' => $familias->map(fn ($f) => [
                        'id' => $f->idfamilia_cl,
                        'description' => $f->descripcion,
                        'description_short' => $f->desc_corta,
                        'available' => $f->estado,
                        'created' => $f->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $f->fmodificacion?->format('Y-m-d H:i:s'),
                    ])->values(),
                    'statistics' => [
                        'products' => ['total' => $products->count()],
                        'categories' => ['total' => $familias->count()],
                        'sports' => ['total' => $deportes->count()],
                    ],
                    'created' => $proveedor->fcreacion?->format('Y-m-d H:i:s'),
                    'updated' => $proveedor->fmodificacion?->format('Y-m-d H:i:s'),
                ];
            });

            $totalTime = microtime(true) - $startTime;

            Log::debug('=== TIEMPO Proveedor Supplier: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

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
            Log::error('Error SuppliersController@showSupplier', [
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
     * Limpiar cache de un proveedor concreto
     *
     * DELETE /api/erp/suppliers/{id}/cache
     */
    public function clearCache(int $id): JsonResponse
    {
        cache()->forget("supplier:detailed:{$id}");
        cache()->forget("supplier:products:{$id}");
        cache()->forget("supplier:categories:{$id}");
        cache()->forget("supplier:supplier:{$id}");

        return response()->json([
            'success' => true,
            'message' => "Cache del proveedor {$id} eliminado",
        ]);
    }

    /**
     * Agrupa artiprovs por modelo y devuelve un producto por modelo.
     * - id/code provienen del modelo (no del artiprov)
     * - description del artiprov pordefecto=1, o el primero del grupo
     * - attributes son todos los articulos del modelo
     */
    private function mapProductsByModel(Collection $artiprovs): Collection
    {
        return $artiprovs
            ->filter(fn ($ap) => $ap->articulo?->modelo !== null)
            ->groupBy(fn ($ap) => $ap->articulo->modelo->idmodelo)
            ->map(function (Collection $group) {
                $modelo = $group->first()->articulo->modelo;

                // Mapa idarticulo => artiprov (del proveedor actual). Un articulo
                // puede no tener artiprov si no lo suministra este proveedor.
                $apByArticulo = $group->keyBy(fn ($ap) => $ap->articulo->idarticulo);

                return [
                    'id' => $modelo->idmodelo,
                    'code' => $modelo->codigo,
                    'name' => $modelo->nombre,
                    'available' => $group->contains('estado', true),
                    'default' => $group->contains('pordefecto', true),
                    'web' => $modelo->estado_publicado_web,
                    'attributes' => $modelo->articulos->map(function ($a) use ($apByArticulo) {
                        $ap = $apByArticulo->get($a->idarticulo);

                        return [
                            'id' => $a->idarticulo,
                            // Datos del artiprov (referencias del proveedor). El código del
                            // artiprov suele ser el SKU real con el que busca el proveedor;
                            // si no hay artiprov, caemos al código del artículo interno.
                            'code' => $ap?->codigo ?: $a->codigo,
                            'code_secundary' => $ap?->codigo2,
                            'ean13' => $ap?->ean13,
                            'upc' => $ap?->upc,
                            'reference' => $a->referencia,
                            // La descripción del artiprov suele ser más rica para buscar en
                            // internet, con fallback a la descripción del artículo.
                            'name' => $ap?->descripcion ?: $a->descripcion,
                            'categorie'    => $a->grupoCl?->subfamiliaCl?->familiaCl?->idfamilia_cl,
                            'subfamily_id' => $a->grupoCl?->subfamiliaCl?->idsubfamilia_cl,
                            'sport_id'     => $a->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->iddeporte_cl,
                            'grupo'        => $a->grupoCl?->idgrupo_cl,
                            'available' => $a->estado,
                            'web' => $a->estado_publicado_web,
                            'created' => $a->fcreacion?->format('Y-m-d H:i:s'),
                            'updated' => $a->fmodificacion?->format('Y-m-d H:i:s'),
                        ];
                    })->values(),
                ];
            })
            ->values();
    }

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
