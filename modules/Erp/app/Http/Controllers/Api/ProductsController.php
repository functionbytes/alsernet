<?php

namespace Modules\Erp\Http\Controllers\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\Oracle\Catalogo\Modelo;
use Modules\Erp\Models\Oracle\Proveedor\Artiprov;
use Modules\Erp\Models\Oracle\Web\WCaracteristicasOrden;
use Modules\Erp\Models\Oracle\Web\WPerfilesProd;

/**
 * VERSIÓN ELOQUENT - GESTIÓN DE MODELOS/PRODUCTOS
 *
 * Endpoints:
 * - GET  /api/erp/products            - Listar modelos con filtros
 * - GET  /api/erp/products/{id}       - Modelo básico
 * - GET  /api/erp/products/{id}/detailed  - Modelo completo: artículos + proveedores + categorías
 * - GET  /api/erp/products/{id}/supplier  - Proveedores del modelo
 * - DELETE /api/erp/products/{id}/cache   - Limpiar caché
 */
class ProductsController extends ApiController
{
    /**
     * Listar modelos con paginación y filtros.
     *
     * GET /api/erp/products
     *
     * Filtros disponibles (query string):
     * - nombre, codigo, estado, idmarca, idgrupo_cl  → columnas directas en MODELO (OCI8 fast)
     * - idproveedor                                   → filtro por proveedor (usa Eloquent)
     */
    public function index(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);
            $idproveedor = $request->get('idproveedor');

            if ($idproveedor) {
                // Obtener idmodelo de artículos suministrados por este proveedor
                $modeloIds = Artiprov::select('articulo.idmodelo')
                    ->join('articulo', 'artiprov.idarticulo', '=', 'articulo.idarticulo')
                    ->where('artiprov.idproveedor', $idproveedor)
                    ->whereNull('artiprov.fbaja')
                    ->whereNull('articulo.fbaja')
                    ->distinct()
                    ->pluck('articulo.idmodelo')
                    ->all();

                $query = Modelo::whereIn('idmodelo', $modeloIds)->whereNull('fbaja');

                foreach (['nombre', 'codigo', 'estado', 'idmarca', 'idgrupo_cl'] as $field) {
                    if ($request->filled($field)) {
                        $val = $request->get($field);
                        $query->where($field, str_contains($val, '%') ? 'LIKE' : '=', $val);
                    }
                }

                $modelos = $query->offset($offset)->limit($limit + 1)->get([
                    'idmodelo', 'codigo', 'nombre', 'estado', 'estado_publicado_web',
                    'idmarca', 'idgrupo_cl', 'fcreacion', 'fmodificacion',
                ]);

                $hasMore = $modelos->count() > $limit;
                $data = $modelos->take($limit)->map(fn ($m) => $this->cleanUtf8Array([
                    'id' => $m->idmodelo,
                    'code' => $m->codigo,
                    'name' => $m->nombre,
                    'available' => $m->estado,
                    'web' => $m->estado_publicado_web,
                    'idmarca' => $m->idmarca,
                    'idgrupo_cl' => $m->idgrupo_cl,
                    'created' => $m->fcreacion?->format('Y-m-d H:i:s'),
                    'updated' => $m->fmodificacion?->format('Y-m-d H:i:s'),
                ]))->values()->all();

                $result = [
                    'success' => true,
                    'data' => $data,
                    'pagination' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'count' => count($data),
                        'hasMore' => $hasMore,
                    ],
                ];
            } else {
                $filters = $request->only(['nombre', 'codigo', 'estado', 'idmarca', 'idgrupo_cl']);
                $result = Modelo::fastPaginate($filters, $limit, $offset);

                if (isset($result['data']) && is_array($result['data'])) {
                    $result['data'] = array_map([$this, 'cleanUtf8Array'], $result['data']);
                }
            }

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Products Index: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error ProductsController@index', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Información básica del modelo.
     *
     * GET /api/erp/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $modelo = Modelo::select([
                'idmodelo', 'codigo', 'nombre', 'descripcion',
                'estado', 'estado_publicado_web', 'idmarca', 'idgrupo_cl',
                'fcreacion', 'fmodificacion',
            ])
                ->with([
                    'marca:idmarca,descripcion',
                    'grupoCl:idgrupo_cl,idsubfamilia_cl',
                    'grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                    'grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,descripcion,desc_corta',
                ])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Product Show: '.round($totalTime * 1000, 2).'ms ===');

            $data = $this->cleanUtf8Array([
                'id' => $modelo->idmodelo,
                'code' => $modelo->codigo,
                'name' => $modelo->nombre,
                'description' => $modelo->descripcion,
                'available' => $modelo->estado,
                'web' => $modelo->estado_publicado_web,
                'categorie' => $modelo->grupoCl?->subfamiliaCl?->familiaCl ? [
                    'id' => $modelo->grupoCl->subfamiliaCl->familiaCl->idfamilia_cl,
                ] : null,
                'created' => $modelo->fcreacion?->format('Y-m-d H:i:s'),
                'updated' => $modelo->fmodificacion?->format('Y-m-d H:i:s'),
            ]);

            return response()->json(['success' => true, 'data' => $data], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'error' => 'Modelo no encontrado'], 404);

        } catch (\Exception $e) {
            Log::error('Error ProductsController@show', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Información completa: modelo + artículos + proveedores + categorías.
     *
     * GET /api/erp/products/{id}/detailed
     */
    public function showDetailed(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult("product:detailed:{$id}", function () use ($id) {
                $modelo = Modelo::select([
                    'idmodelo', 'codigo', 'nombre', 'descripcion',
                    'estado', 'estado_publicado_web', 'idmarca', 'idgrupo_cl',
                    'fcreacion', 'fmodificacion',
                ])
                    ->with([
                        'marca:idmarca,descripcion',
                        'grupoCl:idgrupo_cl,idsubfamilia_cl',
                        'grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                        'grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl,descripcion,desc_corta,estado,fcreacion,fmodificacion',
                        'grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,iddeporte_cl,descripcion',
                        'grupoCl.subfamiliaCl.familiaCl.categoriaCl.deporteCl:iddeporte_cl,descripcion,desc_corta,estado',
                        'articulos' => fn ($q) => $q->whereNull('fbaja')->orderBy('codigo'),
                    ])
                    ->whereNull('fbaja')
                    ->findOrFail($id);

                // Cargar artiprovs y proveedores para los artículos de este modelo
                $articuloIds = $modelo->articulos->pluck('idarticulo')->all();

                $artiprovs = Artiprov::select([
                    'idartiprov', 'idproveedor', 'idarticulo', 'codigo', 'codigo2', 'ean13', 'upc',
                    'descripcion', 'pcosto', 'pordefecto', 'estado',
                ])
                    ->with([
                        'proveedor:idproveedor,nombre,cif,email,estado',
                    ])
                    ->whereIn('idarticulo', $articuloIds)
                    ->whereNull('fbaja')
                    ->get();

                // Características (modelo + por artículo), en batch, sin llamadas HTTP extra
                $modelCharacteristics = $this->modelCharacteristicsByModelo([$id]);
                $variantCharacteristicsByArticulo = $this->variantCharacteristicsByArticulo($articuloIds);

                // Proveedores únicos del modelo
                $proveedores = $artiprovs
                    ->map(fn ($ap) => $ap->proveedor)
                    ->filter()
                    ->unique('idproveedor')
                    ->values();

                $familiaCl = $modelo->grupoCl?->subfamiliaCl?->familiaCl;
                $categoriaCl = $familiaCl?->categoriaCl;
                $deporteCl = $categoriaCl?->deporteCl;

                // Artículos mapeados con sus artiprovs
                $product_attributes = $modelo->articulos->map(function ($a) use ($artiprovs, $variantCharacteristicsByArticulo) {
                    $aps = $artiprovs->where('idarticulo', $a->idarticulo)->values();

                    $apDefault = $aps->firstWhere('pordefecto', true) ?? $aps->first();

                    return [
                        'id' => $a->idarticulo,
                        'code' => $a->codigo,
                        'code_secundary' => $apDefault?->codigo2,
                        'ean13' => $apDefault?->ean13 ?: (trim((string) $a->codbar) !== '' ? $a->codbar : null),
                        'upc' => $apDefault?->upc,
                        'reference' => $apDefault?->codigo ?: $a->referencia,
                        'name' => $apDefault?->descripcion ?: $a->descripcion,
                        'available' => $a->estado,
                        'web' => $a->estado_publicado_web,
                        'web_status' => (int) $a->getRawOriginal('estado_publicado_web'),
                        'categorie' => $a->grupoCl?->subfamiliaCl?->familiaCl?->idfamilia_cl ?? $a->idgrupo_cl,
                        'grupo' => $a->idgrupo_cl,
                        'subfamily_id' => $a->grupoCl?->subfamiliaCl?->idsubfamilia_cl,
                        'sport_id' => $a->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->iddeporte_cl,
                        'supplier' => $aps->map(fn ($ap) => [
                            'id' => $ap->idartiprov,
                            'supplier_id' => $ap->idproveedor,
                            'code' => $ap->codigo,
                            'code_secundary' => $ap->codigo2,
                            'ean13' => $ap->ean13,
                            'upc' => $ap->upc,
                            'description' => $ap->descripcion,
                            'cost' => $ap->pcosto,
                            'default' => $ap->pordefecto,
                            'available' => $ap->estado,
                        ])->values(),
                        'characteristics' => $this->mapVariantCharacteristics($variantCharacteristicsByArticulo->get($a->idarticulo, collect())),
                        'created' => $a->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $a->fmodificacion?->format('Y-m-d H:i:s'),
                    ];
                })->values();

                return [
                    'id' => $modelo->idmodelo,
                    'code' => $modelo->codigo,
                    'name' => $modelo->nombre,
                    'description' => $modelo->descripcion,
                    'available' => $modelo->estado,
                    'web' => $modelo->estado_publicado_web,
                    'web_status' => (int) $modelo->getRawOriginal('estado_publicado_web'),
                    'marca' => $modelo->idmarca,
                    'characteristics' => $this->mapModelCharacteristics($modelCharacteristics->get($id, collect())),
                    'categorie' => $familiaCl ? [
                        'id' => $familiaCl->idfamilia_cl,
                        'description' => $familiaCl->descripcion,
                        'description_short' => $familiaCl->desc_corta,
                        'available' => $familiaCl->estado,
                        'sport' => $deporteCl ? [
                            'id' => $deporteCl->iddeporte_cl,
                            'description' => $deporteCl->descripcion,
                            'description_short' => $deporteCl->desc_corta,
                            'available' => $deporteCl->estado,
                        ] : null,
                    ] : null,
                    'supplier' => ($dp = ($proveedores->first(fn ($p) => $artiprovs->where('idproveedor', $p->idproveedor)->where('pordefecto', true)->isNotEmpty()) ?? $proveedores->first())) ? [
                        'id' => $dp->idproveedor,
                        'name' => $dp->nombre,
                        'cif' => $dp->cif,
                        'email' => $dp->email,
                        'available' => $dp->estado,
                    ] : null,
                    'product_attributes' => $product_attributes,
                    'statistics' => [
                        'product_attributes' => ['total' => $product_attributes->count()],
                    ],
                    'created' => $modelo->fcreacion?->format('Y-m-d H:i:s'),
                    'updated' => $modelo->fmodificacion?->format('Y-m-d H:i:s'),
                ];
            });

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Product Detailed: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data),
                'meta' => ['cached' => $fromCache, 'execution_time_ms' => round($totalTime * 1000, 2)],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'error' => 'Modelo no encontrado'], 404);

        } catch (\Exception $e) {
            Log::error('Error ProductsController@showDetailed', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Proveedores que suministran este modelo.
     *
     * GET /api/erp/products/{id}/supplier
     */
    public function showSupplier(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult("product:suppliers:{$id}", function () use ($id) {
                $modelo = Modelo::select(['idmodelo', 'codigo', 'nombre', 'estado', 'estado_publicado_web'])
                    ->with(['articulos:idarticulo,idmodelo'])
                    ->whereNull('fbaja')
                    ->findOrFail($id);

                $articuloIds = $modelo->articulos->pluck('idarticulo')->all();

                $artiprovs = Artiprov::select(['idartiprov', 'idproveedor', 'idarticulo', 'codigo', 'pcosto', 'pordefecto', 'estado'])
                    ->with(['proveedor:idproveedor,nombre,cif,email,telefono1,estado,fcreacion,fmodificacion'])
                    ->whereIn('idarticulo', $articuloIds)
                    ->whereNull('fbaja')
                    ->get();

                $defaultProveedor = ($artiprovs->firstWhere('pordefecto', true) ?? $artiprovs->first())?->proveedor;

                return [
                    'id' => $modelo->idmodelo,
                    'code' => $modelo->codigo,
                    'name' => $modelo->nombre,
                    'available' => $modelo->estado,
                    'web' => $modelo->estado_publicado_web,
                    'supplier' => $defaultProveedor ? [
                        'id' => $defaultProveedor->idproveedor,
                        'name' => $defaultProveedor->nombre,
                        'cif' => $defaultProveedor->cif,
                        'email' => $defaultProveedor->email,
                        'phone' => $defaultProveedor->telefono1,
                        'available' => $defaultProveedor->estado,
                        'code' => $defaultProveedor->codigo,
                        'created' => $defaultProveedor->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $defaultProveedor->fmodificacion?->format('Y-m-d H:i:s'),
                    ] : null,
                ];
            });

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Product Supplier: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data),
                'meta' => ['cached' => $fromCache, 'execution_time_ms' => round($totalTime * 1000, 2)],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'error' => 'Modelo no encontrado'], 404);

        } catch (\Exception $e) {
            Log::error('Error ProductsController@showSupplier', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Models with name but without description, filtered by web status, brand, color group and/or date range.
     *
     * GET /api/erp/products/filter
     *
     * Query parameters (all optional):
     * - description_empty (int)        Filter by description presence. 1 = empty description, 0 = has description.
     *                                  Omit to return all regardless of description.
     * - web          (int|int[])  Filter by estado_publicado_web. Single value or array.
     *                             Values: 0 (not published), 1 (published), 2 (pending/other)
     *                             Single:  ?web=1
     *                             Multiple: ?web[]=0&web[]=1  (returns both states)
     * - brand_id     (int|int[])  Filter by brand (idmarca). Single value or array.
     *                             Single: ?brand_id=5  |  Multiple: ?brand_id[]=5&brand_id[]=8
     * - color_id     (int|int[])  Filter by color/group (idgrupo_cl). Single value or array.
     *                             Single: ?color_id=12  |  Multiple: ?color_id[]=12&color_id[]=15
     * - date_from    (string)     Filter fcreacion >= date_from. Format: Y-m-d or Y-m-d H:i:s
     *                             If date_from is set but date_to is omitted, date_to defaults to now()
     * - date_to      (string)     Filter fcreacion <= date_to. Format: Y-m-d or Y-m-d H:i:s
     * - idproveedor  (int)        Filter by supplier ERP id (via artiprov join).
     * - limit        (int)        Max results per page. Default: 10, max: 100
     * - offset       (int)        Pagination offset. Default: 0
     *
     * Examples:
     *   GET /api/erp/products/filter?description_empty=1
     *   GET /api/erp/products/filter?description_empty=1&web=1
     *   GET /api/erp/products/filter?web[]=0&web[]=1&brand_id[]=5&brand_id[]=8
     *   GET /api/erp/products/filter?color_id=12&date_from=2026-01-01
     *   GET /api/erp/products/filter?web=1&brand_id=5&date_from=2026-03-01&date_to=2026-04-10
     *   GET /api/erp/products/filter?idproveedor=12&description_empty=1
     */
    public function filter(Request $request): JsonResponse
    {
        // Normalise scalar values to arrays before validation so ?web=1 and ?web[]=1 both work
        foreach (['web', 'brand_id', 'color_id'] as $param) {
            if ($request->has($param) && ! is_array($request->get($param))) {
                $request->merge([$param => [$request->get($param)]]);
            }
        }

        $request->validate([
            'description_empty' => 'sometimes|integer|in:0,1',
            'web' => 'sometimes|array',
            'web.*' => 'integer|in:0,1,2',
            'brand_id' => 'sometimes|array',
            'brand_id.*' => 'integer|min:1',
            'color_id' => 'sometimes|array',
            'color_id.*' => 'integer|min:1',
            'date_from' => 'sometimes|nullable|date',
            'date_to' => 'sometimes|nullable|date',
            'date_field' => 'sometimes|in:creation,modification',
            'idproveedor' => 'sometimes|nullable|integer|min:1',
            'limit' => 'sometimes|integer|min:1|max:100',
            'offset' => 'sometimes|integer|min:0',
        ]);

        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);

            $query = Modelo::select([
                'idmodelo', 'codigo', 'nombre', 'descripcion',
                'estado', 'estado_publicado_web', 'idmarca', 'idgrupo_cl',
                'fcreacion', 'fmodificacion',
            ])
                ->whereNull('fbaja')
                ->whereNotNull('nombre')
                ->whereRaw('LENGTH("NOMBRE") > 0');

            // description_empty=1 → sin descripción | description_empty=0 → con descripción | omitido → todos
            if ($request->has('description_empty')) {
                if ((int) $request->get('description_empty') === 1) {
                    $query->where(function ($q) {
                        $q->whereNull('descripcion')
                            ->orWhereRaw('LENGTH("DESCRIPCION") = 0');
                    });
                } else {
                    $query->whereNotNull('descripcion')
                        ->whereRaw('LENGTH("DESCRIPCION") > 0');
                }
            }

            if ($request->has('web')) {
                $webValues = array_map('intval', $request->get('web'));
                count($webValues) === 1
                    ? $query->where('estado_publicado_web', $webValues[0])
                    : $query->whereIn('estado_publicado_web', $webValues);
            }

            if ($request->filled('brand_id')) {
                $marcas = array_map('intval', $request->get('brand_id'));
                count($marcas) === 1
                    ? $query->where('idmarca', $marcas[0])
                    : $query->whereIn('idmarca', $marcas);
            }

            if ($request->filled('color_id')) {
                $grupos = array_map('intval', $request->get('color_id'));
                count($grupos) === 1
                    ? $query->where('idgrupo_cl', $grupos[0])
                    : $query->whereIn('idgrupo_cl', $grupos);
            }

            // date range por fcreacion o fmodificacion — TO_DATE() explícito para evitar
            // conversión implícita Oracle que depende de NLS_DATE_FORMAT.
            // date_field=creation → FCREACION (defecto), date_field=modification → FMODIFICACION
            $dateCol = $request->get('date_field', 'creation') === 'modification'
                ? '"FMODIFICACION"'
                : '"FCREACION"';

            $dateTo = null;

            if ($request->filled('date_from')) {
                $dateFrom = substr($request->get('date_from'), 0, 10); // solo YYYY-MM-DD
                $query->whereRaw("{$dateCol} >= TO_DATE(?, 'YYYY-MM-DD')", [$dateFrom]);

                $dateTo = $request->filled('date_to')
                    ? substr($request->get('date_to'), 0, 10)
                    : now()->format('Y-m-d');
                $query->whereRaw("{$dateCol} < TO_DATE(?, 'YYYY-MM-DD') + 1", [$dateTo]);
            } elseif ($request->filled('date_to')) {
                $dateTo = substr($request->get('date_to'), 0, 10);
                $query->whereRaw("{$dateCol} < TO_DATE(?, 'YYYY-MM-DD') + 1", [$dateTo]);
            }

            // Filtro por proveedor ERP vía join con artiprov (sin comillas para que Oracle
            // auto-mayusculice los nombres de tabla/columna y evitar ORA-00904)
            if ($request->filled('idproveedor')) {
                $idproveedor = (int) $request->get('idproveedor');
                $query->whereExists(function ($sub) use ($idproveedor) {
                    $sub->selectRaw('1')
                        ->from('artiprov')
                        ->join('articulo', 'artiprov.idarticulo', '=', 'articulo.idarticulo')
                        ->whereRaw('artiprov.idproveedor = ?', [$idproveedor])
                        ->whereRaw('articulo.idmodelo = modelo.idmodelo')
                        ->whereNull('artiprov.fbaja')
                        ->whereNull('articulo.fbaja');
                });
            }

            // Skip the expensive `count()` (full scan on MODELO without index on
            // fmodificacion/nombre). Callers should use `hasMore` for cursor-style
            // pagination. To force a count, pass ?include_total=1.
            $total = $request->boolean('include_total') ? (clone $query)->count() : null;

            $orderByCol = $request->get('date_field', 'creation') === 'modification'
                ? 'fmodificacion'
                : 'fcreacion';

            $modelos = $query
                ->with([
                    'grupoCl:idgrupo_cl,idsubfamilia_cl',
                    'grupoCl.subfamiliaCl:idsubfamilia_cl,idfamilia_cl',
                    'grupoCl.subfamiliaCl.familiaCl:idfamilia_cl,idcategoria_cl,descripcion,desc_corta,estado',
                    'grupoCl.subfamiliaCl.familiaCl.categoriaCl:idcategoria_cl,iddeporte_cl,descripcion',
                    'grupoCl.subfamiliaCl.familiaCl.categoriaCl.deporteCl:iddeporte_cl,descripcion,desc_corta,estado',
                    'articulos' => fn ($q) => $q->whereNull('fbaja')->orderBy('codigo'),
                ])
                ->orderBy($orderByCol, 'desc')
                ->offset($offset)
                ->limit($limit + 1)
                ->get();

            $hasMore = $modelos->count() > $limit;
            $modelos = $modelos->take($limit);

            // Load artiprovs for all articulos in a single query
            $articuloIds = $modelos->flatMap(fn ($m) => $m->articulos->pluck('idarticulo'))->unique()->all();

            $artiprovsByArticulo = Artiprov::select([
                'idartiprov', 'idproveedor', 'idarticulo', 'codigo',
                'descripcion', 'pcosto', 'pordefecto', 'estado',
            ])
                ->with(['proveedor:idproveedor,nombre,cif,email,estado'])
                ->whereIn('idarticulo', $articuloIds)
                ->whereNull('fbaja')
                ->get()
                ->groupBy('idarticulo');

            // Características (modelo + por artículo) para todos los modelos de la página, en batch
            $modeloIds = $modelos->pluck('idmodelo')->all();
            $modelCharacteristicsByModelo = $this->modelCharacteristicsByModelo($modeloIds);
            $variantCharacteristicsByArticulo = $this->variantCharacteristicsByArticulo($articuloIds);

            $data = $modelos->map(function ($modelo) use ($artiprovsByArticulo, $modelCharacteristicsByModelo, $variantCharacteristicsByArticulo) {
                $familiaCl = $modelo->grupoCl?->subfamiliaCl?->familiaCl;
                $categoriaCl = $familiaCl?->categoriaCl;
                $deporteCl = $categoriaCl?->deporteCl;

                $product_attributes = $modelo->articulos->map(function ($a) use ($artiprovsByArticulo, $variantCharacteristicsByArticulo) {
                    $aps = $artiprovsByArticulo->get($a->idarticulo, collect());
                    $apDefault = $aps->firstWhere('pordefecto', true) ?? $aps->first();

                    return [
                        'id' => $a->idarticulo,
                        'code' => $a->codigo,
                        'code_secundary' => $apDefault?->codigo2,
                        'ean13' => $apDefault?->ean13 ?: (trim((string) $a->codbar) !== '' ? $a->codbar : null),
                        'upc' => $apDefault?->upc,
                        'reference' => $apDefault?->codigo ?: $a->referencia,
                        'name' => $apDefault?->descripcion ?: $a->descripcion,
                        'available' => $a->estado,
                        'web' => $a->estado_publicado_web,
                        'web_status' => (int) $a->getRawOriginal('estado_publicado_web'),
                        'categorie' => $a->grupoCl?->subfamiliaCl?->familiaCl?->idfamilia_cl ?? $a->idgrupo_cl,
                        'grupo' => $a->idgrupo_cl,
                        'subfamily_id' => $a->grupoCl?->subfamiliaCl?->idsubfamilia_cl,
                        'sport_id' => $a->grupoCl?->subfamiliaCl?->familiaCl?->categoriaCl?->iddeporte_cl,
                        'supplier' => $aps->map(fn ($ap) => [
                            'id' => $ap->idartiprov,
                            'supplier_id' => $ap->idproveedor,
                            'code' => $ap->codigo,
                            'code_secundary' => $ap->codigo2,
                            'ean13' => $ap->ean13,
                            'upc' => $ap->upc,
                            'description' => $ap->descripcion,
                            'cost' => $ap->pcosto,
                            'default' => $ap->pordefecto,
                            'available' => $ap->estado,
                        ])->values(),
                        'characteristics' => $this->mapVariantCharacteristics($variantCharacteristicsByArticulo->get($a->idarticulo, collect())),
                        'created' => $a->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $a->fmodificacion?->format('Y-m-d H:i:s'),
                    ];
                })->values();

                $modeloArticuloIds = $modelo->articulos->pluck('idarticulo')->all();
                $modeloArtiprovs = collect($modeloArticuloIds)
                    ->flatMap(fn ($id) => $artiprovsByArticulo->get($id, collect()));

                $defaultProveedor = ($modeloArtiprovs->firstWhere('pordefecto', true) ?? $modeloArtiprovs->first())?->proveedor;

                return $this->cleanUtf8Array([
                    'id' => $modelo->idmodelo,
                    'code' => $modelo->codigo,
                    'name' => $modelo->nombre,
                    'description' => $modelo->descripcion,
                    'available' => $modelo->estado,
                    'web' => $modelo->estado_publicado_web,
                    'web_status' => (int) $modelo->getRawOriginal('estado_publicado_web'),
                    'marca' => $modelo->idmarca,
                    'characteristics' => $this->mapModelCharacteristics($modelCharacteristicsByModelo->get($modelo->idmodelo, collect())),
                    'categorie' => $familiaCl ? [
                        'id' => $familiaCl->idfamilia_cl,
                        'description' => $familiaCl->descripcion,
                        'description_short' => $familiaCl->desc_corta,
                        'available' => $familiaCl->estado,
                        'sport' => $deporteCl ? [
                            'id' => $deporteCl->iddeporte_cl,
                            'description' => $deporteCl->descripcion,
                            'description_short' => $deporteCl->desc_corta,
                            'available' => $deporteCl->estado,
                        ] : null,
                    ] : null,
                    'supplier' => $defaultProveedor ? [
                        'id' => $defaultProveedor->idproveedor,
                        'name' => $defaultProveedor->nombre,
                        'cif' => $defaultProveedor->cif,
                        'email' => $defaultProveedor->email,
                        'available' => $defaultProveedor->estado,
                        'code' => $defaultProveedor->codigo,
                    ] : null,
                    'product_attributes' => $product_attributes,
                    'statistics' => [
                        'product_attributes' => ['total' => $product_attributes->count()],
                    ],
                    'created' => $modelo->fcreacion?->format('Y-m-d H:i:s'),
                    'updated' => $modelo->fmodificacion?->format('Y-m-d H:i:s'),
                ]);
            })->values();

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Products Filter: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => $data->count(),
                    'total' => $total,
                    'hasMore' => $hasMore,
                ],
                'meta' => [
                    'filters' => [
                        'description_empty' => $request->has('description_empty') ? (int) $request->get('description_empty') : null,
                        'has_name' => true,
                        'web' => $request->has('web') ? array_map('intval', (array) $request->get('web')) : null,
                        'brand_id' => $request->filled('brand_id') ? array_map('intval', (array) $request->get('brand_id')) : null,
                        'color_id' => $request->filled('color_id') ? array_map('intval', (array) $request->get('color_id')) : null,
                        'date_from' => $request->get('date_from'),
                        'date_to' => $dateTo ?? null,
                        'date_field' => $request->get('date_field', 'creation'),
                    ],
                    'execution_time_ms' => round($totalTime * 1000, 2),
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error ProductsController@filter', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Limpiar caché de un modelo concreto.
     *
     * DELETE /api/erp/products/{id}/cache
     */
    public function clearCache(int $id): JsonResponse
    {
        cache()->forget("product:detailed:{$id}");
        cache()->forget("product:suppliers:{$id}");

        return response()->json(['success' => true, 'message' => "Caché del modelo {$id} eliminado"]);
    }

    /**
     * Características asignadas a nivel modelo (w_caracteristicas_orden + w_caracteristicas_prod),
     * agrupadas por idmodelo. Batched para N modelos en una sola query (patrón artiprovsByArticulo).
     */
    private function modelCharacteristicsByModelo(array $modeloIds): Collection
    {
        return WCaracteristicasOrden::select(['id', 'id_caracteristica', 'idmodelo', 'estado', 'orden'])
            ->with(['caracteristica:id,nombre'])
            ->whereIn('idmodelo', $modeloIds)
            ->whereNull('fbaja')
            ->get()
            ->groupBy('idmodelo');
    }

    /**
     * Características asignadas a nivel artículo/variante (w_perfiles_prod + w_valores_prod +
     * w_caracteristicas_prod), agrupadas por idarticulo. Batched para N artículos en una sola query.
     */
    private function variantCharacteristicsByArticulo(array $articuloIds): Collection
    {
        return WPerfilesProd::select(['id', 'id_valor', 'idarticulo', 'estado', 'orden'])
            ->with(['valor:id,id_caracteristica,nombre', 'valor.caracteristica:id,nombre'])
            ->whereIn('idarticulo', $articuloIds)
            ->whereNull('fbaja')
            ->get()
            ->groupBy('idarticulo');
    }

    private function mapModelCharacteristics(Collection $rows): array
    {
        return $rows->map(fn ($mc) => [
            'id' => $mc->id,
            'characteristic_id' => $mc->id_caracteristica,
            'characteristic_name' => $mc->caracteristica?->nombre,
            'available' => $mc->estado,
            'orden' => $mc->orden,
        ])->values()->all();
    }

    private function mapVariantCharacteristics(Collection $rows): array
    {
        return $rows->map(fn ($vc) => [
            'id' => $vc->id,
            'characteristic_id' => $vc->valor?->id_caracteristica,
            'characteristic_name' => $vc->valor?->caracteristica?->nombre,
            'value_id' => $vc->id_valor,
            'value_name' => $vc->valor?->nombre,
            'available' => $vc->estado,
            'orden' => $vc->orden,
        ])->values()->all();
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
