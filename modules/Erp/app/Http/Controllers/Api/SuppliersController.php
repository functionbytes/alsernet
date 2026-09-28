<?php

namespace Modules\Erp\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Catalogo\Modelo;
use Modules\Erp\Models\Oracle\Proveedor\Artiprov;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;
use Modules\Erp\Support\ErpErrorSanitizer;

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
            $limit = max(1, min((int) $request->get('limit', 10), 100));
            $offset = max(0, (int) $request->get('offset', 0));

            $filters = $request->only(['nombre', 'cif', 'estado']);
            $result = Proveedor::fastPaginate($filters, $limit, $offset, null, $request->integer('after_id') ?: null);

            // Limpiar UTF-8
            if (isset($result['data']) && is_array($result['data'])) {
                $result['data'] = array_map([$this, 'cleanUtf8Array'], $result['data']);
            }

            $totalTime = microtime(true) - $startTime;

            return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error ProveedorController@index', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => ErpErrorSanitizer::forClient($e),
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
                'error' => ErpErrorSanitizer::forClient($e),
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
            $page = $this->supplierModelPage($id);
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult("supplier:detailed:{$id}".($page ? ":p{$page['offset']}-{$page['limit']}" : ''), function () use ($id, $page) {
                $proveedor = $this->findSupplierWithCatalog($id, [
                    'idproveedor', 'nombre', 'cif', 'percontacto',
                    'telefono1', 'telefono2', 'fax', 'email', 'web',
                    'calle', 'num', 'localidad', 'cp', 'provincia',
                    'estado', 'observaciones', 'iban',
                    'idtipoprov', 'idpais', 'idregfiscal',
                    'fcreacion', 'fmodificacion',
                ], [
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
                ], $page['ids'] ?? null);

                $familias = $proveedor->artiprovs
                    ->map(fn ($ap) => $ap->articulo?->modelo)
                    ->filter()
                    ->unique('idmodelo') // cada modelo una vez: repetirlo por artiprov daba 12M elementos (75 s) en proveedores grandes
                    ->flatMap(fn ($modelo) => $modelo->articulos)
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

                $result = [
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

                return $page ? $this->withSupplierPagination($result, $page) : $result;
            });

            $totalTime = microtime(true) - $startTime;

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
                'error' => ErpErrorSanitizer::forClient($e),
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
            $page = $this->supplierModelPage($id);
            $cacheKey = "supplier:products:{$id}".($page ? ":p{$page['offset']}-{$page['limit']}" : '');
            $fromCache = cache()->has($cacheKey);

            $data = cache()->remember($cacheKey, 3600, function () use ($id, $page) {
                $proveedor = $this->findSupplierWithCatalog($id, [
                    'idproveedor',
                    'nombre',
                    'cif',
                    'email',
                    'telefono1',
                    'estado',
                    'fcreacion',
                    'fmodificacion',
                ], [
                    'artiprovs:idartiprov,idproveedor,idarticulo,codigo,codigo2,ean13,upc,descripcion,pcosto,pordefecto,estado',
                    'artiprovs.articulo:idarticulo,codigo,descripcion,idmodelo',
                    'artiprovs.articulo.modelo:idmodelo,codigo,nombre,estado_publicado_web',
                    'artiprovs.articulo.modelo.articulos:idarticulo,codigo,descripcion,referencia,ean_interno,idmodelo,idgrupo_cl,estado,estado_publicado_web,fcreacion,fmodificacion',
                    'artiprovs.articulo.modelo.articulos.grupoCl:idgrupo_cl,idsubfamilia_cl',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl,descripcion,desc_corta,estado',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl,descripcion,desc_corta,estado,fcreacion,fmodificacion',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,iddeporte_cl,descripcion',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl.deporteCl:iddeporte_cl,descripcion,desc_corta,estado',
                ], $page['ids'] ?? null);

                $articulos = $proveedor->artiprovs
                    ->map(fn ($ap) => $ap->articulo?->modelo)
                    ->filter()
                    ->unique('idmodelo') // cada modelo una vez: repetirlo por artiprov daba 12M elementos (75 s) en proveedores grandes
                    ->flatMap(fn ($modelo) => $modelo->articulos);

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

                $result = [
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
                        'id' => $f->idfamilia_cl,
                        'description' => $f->descripcion,
                        'description_short' => $f->desc_corta,
                        'available' => $f->estado,
                        'sport_id' => $f->categoriaCl?->iddeporte_cl,
                        'categoria_id' => $f->categoriaCl?->idcategoria_cl,
                        'categoria_name' => $f->categoriaCl?->descripcion,
                        'categoria_id' => $f->categoriaCl?->idcategoria_cl,
                        'categoria_name' => $f->categoriaCl?->descripcion,
                        'created' => $f->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $f->fmodificacion?->format('Y-m-d H:i:s'),
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

                return $page ? $this->withSupplierPagination($result, $page) : $result;
            });

            $totalTime = microtime(true) - $startTime;

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
                'error' => ErpErrorSanitizer::forClient($e),
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
                $proveedor = $this->findSupplierWithCatalog($id, [
                    'idproveedor',
                    'nombre',
                    'cif',
                    'email',
                    'estado',
                    'fcreacion',
                    'fmodificacion',
                ], [
                    'artiprovs:idartiprov,idproveedor,idarticulo',
                    'artiprovs.articulo:idarticulo,idmodelo',
                    'artiprovs.articulo.modelo:idmodelo',
                    'artiprovs.articulo.modelo.articulos:idarticulo,idmodelo,idgrupo_cl',
                    'artiprovs.articulo.modelo.articulos.grupoCl:idgrupo_cl,idsubfamilia_cl',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl,descripcion,desc_corta,estado,fcreacion,fmodificacion',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,iddeporte_cl,descripcion',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl.deporteCl:iddeporte_cl,descripcion,desc_corta,estado',
                ]);

                $familias = $proveedor->artiprovs
                    ->map(fn ($ap) => $ap->articulo?->modelo)
                    ->filter()
                    ->unique('idmodelo') // cada modelo una vez: repetirlo por artiprov daba 12M elementos (75 s) en proveedores grandes
                    ->flatMap(fn ($modelo) => $modelo->articulos)
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
                'error' => ErpErrorSanitizer::forClient($e),
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
                $proveedor = $this->findSupplierWithCatalog($id, [
                    'idproveedor', 'nombre', 'cif', 'email', 'estado', 'fcreacion', 'fmodificacion',
                ], [
                    'artiprovs:idartiprov,idproveedor,idarticulo,codigo,codigo2,ean13,upc,descripcion,pcosto,pordefecto,estado',
                    'artiprovs.articulo:idarticulo,codigo,descripcion,idmodelo,estado_publicado_web',
                    'artiprovs.articulo.modelo:idmodelo,codigo,nombre,descripcion,estado_publicado_web',
                    'artiprovs.articulo.modelo.articulos:idarticulo,codigo,descripcion,referencia,ean_interno,idmodelo,idgrupo_cl,estado,estado_publicado_web,fcreacion,fmodificacion',
                    'artiprovs.articulo.modelo.articulos.grupoCl:idgrupo_cl,idsubfamilia_cl',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl,descripcion,desc_corta,estado,fcreacion,fmodificacion',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,iddeporte_cl,descripcion',
                    'artiprovs.articulo.modelo.articulos.grupoCl.subfamiliaCl.familiaCl.categoriaCl.deporteCl:iddeporte_cl,descripcion,desc_corta,estado',
                ]);

                // Filtrar: modelo sin descripción Y web=true
                $filtered = $proveedor->artiprovs->filter(
                    fn ($ap) => empty($ap->articulo?->modelo?->descripcion)
                        && $ap->articulo?->modelo?->estado_publicado_web === true
                )->values();

                $familias = $filtered
                    ->map(fn ($ap) => $ap->articulo?->modelo)
                    ->filter()
                    ->unique('idmodelo') // cada modelo una vez: repetirlo por artiprov daba 12M elementos (75 s) en proveedores grandes
                    ->flatMap(fn ($modelo) => $modelo->articulos)
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
                'error' => ErpErrorSanitizer::forClient($e),
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
    /**
     * Paginación OPCIONAL por modelo para /suppliers/{id}/products y
     * /detailed (?limit=N&offset=M, máx. 500). Sin `limit` devuelve null y la
     * respuesta es la de siempre, completa (la consume así la sincronización
     * de Supplier). Para un proveedor de 14k artículos la respuesta completa
     * son ~7 MB y ~20 s; una página de 200 modelos, unos cientos de ms.
     *
     * @return array{ids: array<int, int>, limit: int, offset: int, total: int, hasMore: bool}|null
     */
    private function supplierModelPage(int $id): ?array
    {
        $request = request();
        if (! $request->filled('limit')) {
            return null;
        }

        $limit = max(1, min((int) $request->query('limit'), 500));
        $offset = max(0, (int) $request->query('offset', 0));

        $base = fn () => Modelo::query()
            ->join('articulo', 'articulo.idmodelo', '=', 'modelo.idmodelo')
            ->join('artiprov', 'artiprov.idarticulo', '=', 'articulo.idarticulo')
            ->where('artiprov.idproveedor', $id)
            ->whereNull('artiprov.fbaja')
            ->whereNull('articulo.fbaja')
            ->whereNull('modelo.fbaja');

        $ids = $base()->distinct()->orderBy('modelo.idmodelo')
            ->offset($offset)->limit($limit + 1)
            ->pluck('modelo.idmodelo')
            ->map(fn ($v) => (int) $v)
            ->all();

        $hasMore = count($ids) > $limit;

        return [
            'ids' => array_slice($ids, 0, $limit),
            'limit' => $limit,
            'offset' => $offset,
            'total' => (int) $base()->distinct()->count('modelo.idmodelo'),
            'hasMore' => $hasMore,
        ];
    }

    /**
     * Ordena los productos de una página según el orden de la página
     * (idmodelo ascendente) y añade el bloque `pagination` a la respuesta.
     *
     * @param  array<string, mixed>  $data
     * @param  array{ids: array<int, int>, limit: int, offset: int, total: int, hasMore: bool}  $page
     * @return array<string, mixed>
     */
    private function withSupplierPagination(array $data, array $page): array
    {
        $position = array_flip($page['ids']);
        $data['products'] = collect($data['products'])
            ->sortBy(fn ($p) => $position[(int) $p['id']] ?? PHP_INT_MAX)
            ->values();
        $data['pagination'] = [
            'limit' => $page['limit'],
            'offset' => $page['offset'],
            'count' => count($data['products']),
            'total' => $page['total'],
            'hasMore' => $page['hasMore'],
            // sports/categories/subfamilies son los de los productos de la
            // página; la taxonomía completa del proveedor está en /categories.
            'taxonomy_scope' => 'page',
        ];

        return $data;
    }

    /**
     * Proveedor + su catálogo (artiprovs → articulo → modelo → artículos del
     * modelo → jerarquía) con el mismo árbol de relaciones que devolvía
     * `->with([...])`, pero sin sus listas IN gigantes.
     *
     * Eloquent resolvía cada nivel con `WHERE id IN (miles de ids)` troceado
     * de 1000 en 1000: para un proveedor de 14k artículos eran 65 s solo en
     * ARTICULO y 200 s en MODELO (su DESCRIPCION es CLOB). Aquí cada nivel es
     * un JOIN que parte del índice de ARTIPROV por IDPROVEEDOR (con
     * `IN (subconsulta)` el plan de Oracle, sin estadísticas, saltaba entre
     * 0,1 s y 24 s), la CLOB se lee en línea y la jerarquía se carga sobre
     * los grupos distintos (unos cientos).
     *
     * Acepta el mismo array de `with` que antes: las claves 'artiprovs*' se
     * resuelven aquí y el resto se cargan normalmente sobre el proveedor.
     * Aplica los mismos filtros que la cadena de relaciones (SoftDeletes).
     *
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $with
     */
    private function findSupplierWithCatalog(int $id, array $columns, array $with, ?array $modeloIds = null): Proveedor
    {
        $levels = ['artiprovs' => null, 'artiprovs.articulo' => null, 'artiprovs.articulo.modelo' => null, 'artiprovs.articulo.modelo.articulos' => null];
        $hierarchy = [];
        $other = [];
        foreach ($with as $relation) {
            [$path, $cols] = array_pad(explode(':', $relation, 2), 2, null);
            if (array_key_exists($path, $levels)) {
                $levels[$path] = $cols !== null ? explode(',', $cols) : ['*'];
            } elseif (str_starts_with($path, 'artiprovs.articulo.modelo.articulos.')) {
                $hierarchy[] = substr($relation, strlen('artiprovs.articulo.modelo.articulos.'));
            } else {
                $other[] = $relation;
            }
        }

        $proveedor = Proveedor::select($columns)->with($other)->whereNull('fbaja')->findOrFail($id);

        // Modo paginado: ids de la página como literales enteros. Con binds, un
        // `IN (?, ?, … ×200)` tardaba 6,8 s en ARTICULO aun teniendo índice por
        // IDMODELO; en literal usa el índice (son ints generados aquí, sin
        // entrada del usuario).
        $inModelos = $modeloIds === null ? '' : (implode(',', array_map('intval', $modeloIds)) ?: '-1');

        $artiprovs = Artiprov::select($levels['artiprovs'])
            ->where('idproveedor', $id)
            // Modo paginado: solo los artiprovs de los modelos de la página.
            ->when($modeloIds !== null, fn ($q) => $q->whereIn('idarticulo', fn ($sub) => $sub->select('idarticulo')
                ->from('articulo')->whereRaw('idmodelo IN ('.$inModelos.')')->whereNull('fbaja')))
            ->get();
        $proveedor->setRelation('artiprovs', $artiprovs);
        if ($artiprovs->isEmpty() || $levels['artiprovs.articulo'] === null) {
            return $proveedor;
        }

        $qualify = fn (array $cols, string $table) => array_map(fn ($c) => $table.'.'.$c, $cols);

        $articulos = Articulo::select($qualify($levels['artiprovs.articulo'], 'articulo'))
            ->join('artiprov', 'artiprov.idarticulo', '=', 'articulo.idarticulo')
            ->where('artiprov.idproveedor', $id)
            ->whereNull('artiprov.fbaja')
            ->when($modeloIds !== null, fn ($q) => $q->whereRaw('articulo.idmodelo IN ('.$inModelos.')'))
            ->distinct()
            ->get()
            ->keyBy('idarticulo');

        $modelos = collect();
        if ($levels['artiprovs.articulo.modelo'] !== null) {
            $modeloCols = $levels['artiprovs.articulo.modelo'];
            $inlineDescripcion = in_array('descripcion', $modeloCols, true);
            $query = Modelo::select($qualify(array_values(array_diff($modeloCols, ['descripcion'])), 'modelo'))
                ->join('articulo', 'articulo.idmodelo', '=', 'modelo.idmodelo')
                ->join('artiprov', 'artiprov.idarticulo', '=', 'articulo.idarticulo')
                ->where('artiprov.idproveedor', $id)
                ->whereNull('artiprov.fbaja')
                ->whereNull('articulo.fbaja')
                ->when($modeloIds !== null, fn ($q) => $q->whereRaw('modelo.idmodelo IN ('.$inModelos.')'))
                ->distinct();
            if ($inlineDescripcion) {
                $query->withInlineDescripcion('modelo');
            }
            $modelos = $query->get()->keyBy('idmodelo');
            if ($inlineDescripcion) {
                Modelo::hydrateLongDescriptions($modelos);
            }

            if ($levels['artiprovs.articulo.modelo.articulos'] !== null) {
                // Todos los artículos de esos modelos, no solo los del proveedor.
                // Subconsulta con alias (art_prov): sin alias, el FBAJA de la
                // consulta exterior quedaba ambiguo con el ARTICULO interior y el
                // plan se disparaba. Los modelos dados de baja se descartan
                // al asignar (solo se asignan los de $modelos).
                $articuloCols = $qualify($levels['artiprovs.articulo.modelo.articulos'], 'articulo');
                $articulosDeModelos = $modeloIds !== null
                    // Modo paginado: los modelos de la página. Hint de índice
                    // porque, sin estadísticas, Oracle recorría ARTICULO entera
                    // (2,3 s frente a 0,2 s para 200 modelos).
                    ? Articulo::fromQuery(
                        'SELECT /*+ INDEX(articulo IDX_ARTICULO_IDMODELO) */ '.implode(', ', $articuloCols)
                        .' FROM DEVELOPER.ARTICULO articulo'
                        .' WHERE articulo.idmodelo IN ('.$inModelos.') AND articulo.fbaja IS NULL'
                        .' ORDER BY articulo.idarticulo'
                    )
                    : Articulo::select($articuloCols)
                        ->whereIn('articulo.idmodelo', fn ($sub) => $sub->select('art_prov.idmodelo')
                            ->from('articulo as art_prov')
                            ->join('artiprov', 'artiprov.idarticulo', '=', 'art_prov.idarticulo')
                            ->where('artiprov.idproveedor', $id)
                            ->whereNull('artiprov.fbaja')
                            ->whereNull('art_prov.fbaja'))
                        ->orderBy('articulo.idarticulo')
                        ->get();
                if ($hierarchy !== []) {
                    // Eager load sobre la colección: IN de los grupos distintos.
                    $articulosDeModelos->load($hierarchy);
                }
                $byModelo = $articulosDeModelos->groupBy('idmodelo');
                foreach ($modelos as $idmodelo => $modelo) {
                    $modelo->setRelation('articulos', new EloquentCollection($byModelo->get($idmodelo)?->all() ?? []));
                }
            }
        }

        foreach ($articulos as $articulo) {
            $articulo->setRelation('modelo', $modelos->get($articulo->idmodelo));
        }
        foreach ($artiprovs as $artiprov) {
            $artiprov->setRelation('articulo', $articulos->get($artiprov->idarticulo));
        }

        return $proveedor;
    }

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
                            'categorie' => $a->grupoCl?->subfamiliaCl?->familiaCl?->idfamilia_cl,
                            'subfamily_id' => $a->grupoCl?->subfamiliaCl?->idsubfamilia_cl,
                            'sport_id' => $a->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->iddeporte_cl,
                            'grupo' => $a->grupoCl?->idgrupo_cl,
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
