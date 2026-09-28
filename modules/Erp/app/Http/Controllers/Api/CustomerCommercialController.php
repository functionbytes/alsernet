<?php

namespace Modules\Erp\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\Oracle\Albaran\AlbarancliCentral;
use Modules\Erp\Models\Oracle\Albaran\LalbarancliCentral;
use Modules\Erp\Models\Oracle\Cliente\ClienteCent;
use Modules\Erp\Models\Oracle\Factura\FacturacliCentral;
use Modules\Erp\Models\Oracle\Factura\LfacturacliCentral;
use Modules\Erp\Models\Oracle\Pedido\PedidocliCentral;
use Modules\Erp\Services\HelpdeskWebhookSender;
use Modules\Erp\Services\OCI8Service;
use Modules\Erp\Support\CustomerOrdersQuery;
use Modules\Erp\Support\ErpErrorSanitizer;

/**
 * Documentos comerciales del cliente: pedidos, albaranes y facturas.
 *
 * Base URL: /api/erp/customer/{id}/...
 */
class CustomerCommercialController extends AbstractCustomerController
{
    /**
     * Lista paginada de pedidos del cliente (cabecera).
     *
     * GET /api/erp/customer/{id}/orders?limit=10&offset=0&status=&from=&to=
     */
    public function orders(int $id, Request $request): JsonResponse
    {
        try {
            $limit = max(1, min((int) $request->get('limit', 10), 100));
            $offset = max(0, (int) $request->get('offset', 0));
            $status = (string) $request->get('status', '');
            $from = (string) $request->get('from', '');
            $to = (string) $request->get('to', '');

            $cacheKey = CustomerOrdersQuery::cacheKey($id, $status, $from, $to, $offset, $limit);
            $cached = cache()->get($cacheKey);

            if ($cached !== null) {
                $rows = $cached;
                $hasMore = count($rows) > $limit;
                if ($hasMore) {
                    $rows = array_slice($rows, 0, $limit);
                }

                return response()->json([
                    'success' => true,
                    'data' => $this->cleanUtf8Array($this->mapOrders($rows)),
                    'pagination' => ['limit' => $limit, 'offset' => $offset, 'count' => count($rows), 'hasMore' => $hasMore],
                    'meta' => ['cached' => true],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            // Cache miss: carga en background DESPUÉS de enviar la respuesta al cliente.
            // fastcgi_finish_request() cierra la conexión HTTP pero el worker PHP-FPM
            // sigue vivo para ejecutar la query Oracle (sin límite de timeout HTTP).
            // Pendiente DBA: CREATE INDEX IDX_PEDIDOCLI_IDCLIENTE ON DEVELOPER.PEDIDOCLI_CENTRAL(IDCLIENTE, FBAJA)
            [$sql, $bindings] = CustomerOrdersQuery::build($id, $status, $from, $to, $offset, $limit);

            // Síncrono por defecto: con ORDER BY la consulta tarda ~1 s (un
            // recorrido de PEDIDOCLI_CENTRAL, sin índice por IDCLIENTE aún), así
            // que ya no compensa responder vacío con `loading` y obligar al
            // cliente a reintentar a los 35 s. El webhook orders-ready se sigue
            // enviando tras la respuesta (lo consumen Helpdesk y ChatFlow).
            // ERP_ORDERS_SYNC=false vuelve a la carga en segundo plano.
            if (config('erp.orders.sync', true)) {
                $rows = app(OCI8Service::class)->query($sql, $bindings);
                cache()->put($cacheKey, $rows, now()->addHour());
                register_shutdown_function(fn () => $this->notifyOrdersReady($id));

                $hasMore = count($rows) > $limit;
                if ($hasMore) {
                    $rows = array_slice($rows, 0, $limit);
                }

                return response()->json([
                    'success' => true,
                    'data' => $this->cleanUtf8Array($this->mapOrders($rows)),
                    'pagination' => ['limit' => $limit, 'offset' => $offset, 'count' => count($rows), 'hasMore' => $hasMore],
                    'meta' => ['cached' => false],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            $lockKey = "orders_loading_lock:{$id}";
            $lock = Cache::lock($lockKey, 120);
            if (! $lock->get()) {
                // Another worker already owns the background scan; just return
                // the empty placeholder and let them populate the cache.
                Log::info("Orders cache background: skipped, lock held for customer {$id}");
            } else {
                register_shutdown_function(function () use ($id, $cacheKey, $sql, $bindings, $lock) {
                    if (function_exists('fastcgi_finish_request')) {
                        fastcgi_finish_request();
                    }
                    try {
                        // Double-check after acquiring the lock — a peer may have
                        // populated the cache between request entry and shutdown.
                        if (cache()->has($cacheKey)) {
                            return;
                        }

                        $oci8 = app(OCI8Service::class);
                        $rows = $oci8->query($sql, $bindings);
                        cache()->put($cacheKey, $rows, now()->addHour());
                        Log::info("Orders cache background: customer {$id}, ".count($rows).' rows');

                        $this->notifyOrdersReady($id);
                    } catch (\Throwable $e) {
                        Log::warning('Orders cache background failed', ['id' => $id, 'error' => $e->getMessage()]);
                    } finally {
                        // forceRelease — we're in a shutdown handler so the lock
                        // owner check via getCurrentLockOwner could be brittle.
                        try {
                            $lock->forceRelease();
                        } catch (\Throwable) {
                            // best-effort
                        }
                    }
                });
            }

            return response()->json([
                'success' => true,
                'data' => [],
                'pagination' => ['limit' => $limit, 'offset' => $offset, 'count' => 0, 'hasMore' => false],
                'meta' => ['cached' => false, 'available' => false, 'loading' => true, 'retry_after' => 35],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@orders', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }

    /**
     * Avisa al Helpdesk de que los pedidos del cliente están en caché
     * (webhook orders-ready: refresco del panel y flujos de ChatFlow).
     */
    private function notifyOrdersReady(int $id): void
    {
        try {
            // Con el modelo: table('DEVELOPER.CLIENTE_CENT') duplicaba el esquema
            // (la conexión ya lo antepone) y fallaba con ORA-00907, así que el
            // webhook orders-ready no se llegaba a enviar nunca.
            $email = (string) ClienteCent::withTrashed()->whereKey($id)->value('email');
            if ($email) {
                app(HelpdeskWebhookSender::class)->notifyOrdersReady($email, $id);
            }
        } catch (\Throwable $e) {
            Log::info('Helpdesk webhook not sent', ['id' => $id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Detalle de un pedido del cliente con líneas y artículos.
     *
     * GET /api/erp/customer/{id}/orders/{orderId}
     */
    public function orderDetail(int $id, int $orderId): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult(
                "customer:order:{$id}:{$orderId}",
                function () use ($id, $orderId) {
                    $order = PedidocliCentral::select([
                        'idpedidocli_central', 'idpedidocli', 'idcliente', 'idalmacen',
                        'estado', 'npedidocli', 'fpedido', 'fprevista', 'fservido',
                        'observaciones', 'tipopedido', 'idorigenpedidocli',
                        'idcatalogo', 'idprioridad', 'idregfiscal', 'idclientecuenta',
                        'solicitafactura', 'facturado', 'fcreacion', 'fmodificacion',
                    ])
                        ->with([
                            'almacen:idalmacen,descripcion',
                            'estadoInfo:estado,descripcion',
                            'origenpedidocli:idorigenpedidocli,descripcion',
                            'prioridad:idprioridad,descripcion',
                            'catalogo:idcatalogo,descripcion',
                            'lineas:idlpedidocli_central,idpedidocli_central,idarticulo,unidades,precio,dto,iva,recargo,estado,notapieza,notageneral,fcreacion',
                            'lineas.articulo:idarticulo,codigo,descripcion,referencia,ean_interno,idmodelo,estado',
                            'formasPago:idfppedcli_central,idpedidocli_central,idformapago,importe,estado',
                            'formasPago.formapago:idformapago,descripcion',
                        ])
                        ->where('idcliente', $id)
                        ->whereNull('fbaja')
                        ->where('idpedidocli_central', $orderId)
                        ->firstOrFail();

                    $lines = $order->lineas->map(function ($l) {
                        $unidades = (float) $l->unidades;
                        $precio = (float) $l->precio;
                        $dto = (float) $l->dto;
                        $subtotal = round($unidades * $precio * (1 - $dto / 100), 2);

                        return [
                            'id' => $l->idlpedidocli_central,
                            'article' => [
                                'id' => $l->articulo?->idarticulo,
                                'code' => $l->articulo?->codigo,
                                'description' => $l->articulo?->descripcion,
                                'reference' => $l->articulo?->referencia,
                                'ean' => $l->articulo?->ean_interno,
                                'model_id' => $l->articulo?->idmodelo,
                                'available' => $l->articulo?->estado,
                            ],
                            'units' => $unidades,
                            'price' => $precio,
                            'discount_percent' => $dto,
                            'tax_percent' => (float) $l->iva,
                            'surcharge_percent' => (float) $l->recargo,
                            'subtotal' => $subtotal,
                            'piece_note' => $l->notapieza,
                            'general_note' => $l->notageneral,
                            'available' => $l->estado,
                            'created' => $l->fcreacion?->format('Y-m-d H:i:s'),
                        ];
                    })->values();

                    $payments = $order->formasPago->map(fn ($fp) => [
                        'id' => $fp->idfppedcli_central,
                        'method_id' => $fp->idformapago,
                        'method' => $fp->formapago?->descripcion,
                        'amount' => (float) $fp->importe,
                        'available' => $fp->estado,
                    ])->values();

                    $totalLines = $lines->sum('subtotal');

                    return [
                        'id' => $order->idpedidocli_central,
                        'order_id' => $order->idpedidocli,
                        'number' => $order->npedidocli,
                        'status' => $order->estado,
                        'status_description' => $order->estadoInfo?->descripcion,
                        'warehouse' => [
                            'id' => $order->idalmacen,
                            'description' => $order->almacen?->descripcion,
                        ],
                        'origin' => [
                            'id' => $order->idorigenpedidocli,
                            'description' => $order->origenpedidocli?->descripcion,
                        ],
                        'priority' => [
                            'id' => $order->idprioridad,
                            'description' => $order->prioridad?->descripcion,
                        ],
                        'catalog' => [
                            'id' => $order->idcatalogo,
                            'description' => $order->catalogo?->descripcion,
                        ],
                        'type' => $order->tipopedido,
                        'invoiced' => (bool) $order->facturado,
                        'requested_invoice' => (bool) $order->solicitafactura,
                        'date' => $order->fpedido?->format('Y-m-d H:i:s'),
                        'expected_date' => $order->fprevista?->format('Y-m-d'),
                        'served_date' => $order->fservido?->format('Y-m-d H:i:s'),
                        'observations' => $order->observaciones,
                        'created' => $order->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $order->fmodificacion?->format('Y-m-d H:i:s'),

                        'lines' => $lines,
                        'payments' => $payments,

                        'totals' => [
                            'lines_total' => round($totalLines, 2),
                            'payments_total' => round($payments->sum('amount'), 2),
                        ],

                        'statistics' => [
                            'lines' => ['total' => $lines->count()],
                            'payments' => ['total' => $payments->count()],
                        ],
                    ];
                }
            );

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
            return response()->json(['success' => false, 'error' => 'Order not found for this customer'], 404);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@orderDetail', [
                'error' => $e->getMessage(),
                'id' => $id,
                'orderId' => $orderId,
            ]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }

    /**
     * Albaranes del cliente (paginado).
     *
     * GET /api/erp/customer/{id}/delivery-notes?limit=10&offset=0&from=&to=
     */
    public function deliveryNotes(int $id, Request $request): JsonResponse
    {
        try {
            $limit = max(1, min((int) $request->get('limit', 10), 100));
            $offset = max(0, (int) $request->get('offset', 0));

            $query = AlbarancliCentral::select([
                'idalbarancli_central', 'idalbarancli', 'idcliente', 'idalmacen',
                'estado', 'tipo', 'nalbarancli', 'falbaran', 'observaciones',
                'idcatalogo', 'puntosfideliz', 'idfacturacli', 'fcreacion', 'fmodificacion',
            ])
                ->where('idcliente', $id)
                ->whereNull('fbaja');

            if ($request->filled('status')) {
                $query->where('estado', $request->get('status'));
            }

            if ($request->filled('from')) {
                $query->where('falbaran', '>=', $request->get('from'));
            }

            if ($request->filled('to')) {
                $query->where('falbaran', '<=', $request->get('to'));
            }

            $deliveries = $query->orderByDesc('falbaran')
                ->offset($offset)
                ->limit($limit + 1)
                ->get();

            $hasMore = $deliveries->count() > $limit;
            if ($hasMore) {
                $deliveries = $deliveries->slice(0, $limit);
            }

            $data = $deliveries->map(fn ($d) => [
                'id' => $d->idalbarancli_central,
                'delivery_id' => $d->idalbarancli,
                'number' => $d->nalbarancli,
                'status' => $d->estado,
                'type' => $d->tipo,
                'warehouse' => $d->idalmacen,
                'catalog' => $d->idcatalogo,
                'invoice_id' => $d->idfacturacli,
                'loyalty_points' => (int) ($d->puntosfideliz ?? 0),
                'date' => $d->falbaran?->format('Y-m-d H:i:s'),
                'observations' => $d->observaciones,
                'created' => $d->fcreacion?->format('Y-m-d H:i:s'),
                'updated' => $d->fmodificacion?->format('Y-m-d H:i:s'),
            ])->values();

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data->all()),
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => $deliveries->count(),
                    'hasMore' => $hasMore,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@deliveryNotes', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }

    /**
     * Detalle de albarán con líneas.
     *
     * GET /api/erp/customer/{id}/delivery-notes/{deliveryId}
     */
    public function deliveryNoteDetail(int $id, int $deliveryId): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult(
                "customer:delivery:{$id}:{$deliveryId}",
                function () use ($id, $deliveryId) {
                    $delivery = AlbarancliCentral::select([
                        'idalbarancli_central', 'idalbarancli', 'idcliente', 'idalmacen',
                        'estado', 'tipo', 'nalbarancli', 'falbaran', 'observaciones',
                        'idcatalogo', 'puntosfideliz', 'idfacturacli', 'solicita_factura',
                        'fcreacion', 'fmodificacion',
                    ])
                        ->where('idcliente', $id)
                        ->whereNull('fbaja')
                        ->where('idalbarancli_central', $deliveryId)
                        ->firstOrFail();

                    $lines = LalbarancliCentral::select([
                        'idlalbarancli_central', 'idalbarancli_central', 'idarticulo',
                        'unidades', 'precio', 'dto', 'iva', 'recargo',
                        'total_bi', 'total_con_impuestos', 'total_neto',
                        'notapieza', 'notageneral', 'fcreacion',
                    ])
                        ->where('idalbarancli_central', $delivery->idalbarancli_central)
                        ->whereNull('fbaja')
                        ->with(['articulo:idarticulo,codigo,descripcion,referencia,ean_interno,idmodelo'])
                        ->get()
                        ->map(fn ($l) => [
                            'id' => $l->idlalbarancli_central,
                            'article' => [
                                'id' => $l->articulo?->idarticulo,
                                'code' => $l->articulo?->codigo,
                                'description' => $l->articulo?->descripcion,
                                'reference' => $l->articulo?->referencia,
                                'ean' => $l->articulo?->ean_interno,
                                'model_id' => $l->articulo?->idmodelo,
                            ],
                            'units' => (float) $l->unidades,
                            'price' => (float) $l->precio,
                            'discount_percent' => (float) $l->dto,
                            'tax_percent' => (float) $l->iva,
                            'surcharge_percent' => (float) $l->recargo,
                            'subtotal' => $l->total_bi !== null ? (float) $l->total_bi : null,
                            'total_with_taxes' => $l->total_con_impuestos !== null ? (float) $l->total_con_impuestos : null,
                            'total_net' => $l->total_neto !== null ? (float) $l->total_neto : null,
                            'piece_note' => $l->notapieza,
                            'general_note' => $l->notageneral,
                            'created' => $l->fcreacion?->format('Y-m-d H:i:s'),
                        ])->values();

                    return [
                        'id' => $delivery->idalbarancli_central,
                        'delivery_id' => $delivery->idalbarancli,
                        'number' => $delivery->nalbarancli,
                        'status' => $delivery->estado,
                        'type' => $delivery->tipo,
                        'warehouse' => $delivery->idalmacen,
                        'catalog' => $delivery->idcatalogo,
                        'invoice_id' => $delivery->idfacturacli,
                        'requested_invoice' => (bool) $delivery->solicita_factura,
                        'loyalty_points' => (int) ($delivery->puntosfideliz ?? 0),
                        'date' => $delivery->falbaran?->format('Y-m-d H:i:s'),
                        'observations' => $delivery->observaciones,
                        'lines' => $lines,
                        'totals' => [
                            'lines_total_bi' => round((float) $lines->sum('subtotal'), 2),
                            'lines_total_with_taxes' => round((float) $lines->sum('total_with_taxes'), 2),
                            'lines_total_net' => round((float) $lines->sum('total_net'), 2),
                        ],
                        'statistics' => [
                            'lines' => ['total' => $lines->count()],
                        ],
                        'created' => $delivery->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $delivery->fmodificacion?->format('Y-m-d H:i:s'),
                    ];
                }
            );

            $totalTime = microtime(true) - $startTime;

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data),
                'meta' => ['cached' => $fromCache, 'execution_time_ms' => round($totalTime * 1000, 2)],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Delivery note not found for this customer'], 404);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@deliveryNoteDetail', ['error' => $e->getMessage(), 'id' => $id, 'deliveryId' => $deliveryId]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }

    /**
     * Facturas del cliente (paginado).
     *
     * GET /api/erp/customer/{id}/invoices?limit=10&offset=0&year=&from=&to=
     */
    public function invoices(int $id, Request $request): JsonResponse
    {
        try {
            $limit = max(1, min((int) $request->get('limit', 10), 100));
            $offset = max(0, (int) $request->get('offset', 0));

            $query = FacturacliCentral::select([
                'idfacturacli', 'idcliente', 'iddeuda', 'idserie', 'nfactura',
                'anno', 'ffactura', 'tipo', 'idformapago', 'estado',
                'idalmacen', 'idcatalogo', 'simplificada', 'observaciones',
                'fcreacion', 'fmodificacion',
            ])
                ->where('idcliente', $id)
                ->whereNull('fbaja');

            if ($request->filled('status')) {
                $query->where('estado', $request->get('status'));
            }

            if ($request->filled('year')) {
                $query->where('anno', $request->get('year'));
            }

            if ($request->filled('from')) {
                $query->where('ffactura', '>=', $request->get('from'));
            }

            if ($request->filled('to')) {
                $query->where('ffactura', '<=', $request->get('to'));
            }

            $invoices = $query->orderByDesc('ffactura')
                ->offset($offset)
                ->limit($limit + 1)
                ->get();

            $hasMore = $invoices->count() > $limit;
            if ($hasMore) {
                $invoices = $invoices->slice(0, $limit);
            }

            $data = $invoices->map(fn ($i) => [
                'id' => $i->idfacturacli,
                'series' => $i->idserie,
                'number' => $i->nfactura,
                'year' => $i->anno,
                'date' => $i->ffactura ? Carbon::parse($i->ffactura)->format('Y-m-d') : null,
                'type' => $i->tipo,
                'simplified' => (bool) $i->simplificada,
                'payment_method' => $i->idformapago,
                'warehouse' => $i->idalmacen,
                'catalog' => $i->idcatalogo,
                'debt_id' => $i->iddeuda,
                'status' => $i->estado,
                'observations' => $i->observaciones,
                'created' => $i->fcreacion?->format('Y-m-d H:i:s'),
                'updated' => $i->fmodificacion?->format('Y-m-d H:i:s'),
            ])->values();

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data->all()),
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => $invoices->count(),
                    'hasMore' => $hasMore,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@invoices', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }

    /**
     * Detalle de factura con líneas.
     *
     * GET /api/erp/customer/{id}/invoices/{invoiceId}
     */
    public function invoiceDetail(int $id, int $invoiceId): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult(
                "customer:invoice:{$id}:{$invoiceId}",
                function () use ($id, $invoiceId) {
                    $invoice = FacturacliCentral::select([
                        'idfacturacli', 'idcliente', 'iddeuda', 'idserie', 'nfactura',
                        'anno', 'ffactura', 'tipo', 'idformapago', 'estado',
                        'idalmacen', 'idcatalogo', 'simplificada', 'observaciones',
                        'nombre', 'cif', 'calle', 'numero', 'localidad', 'cp', 'provincia', 'pais',
                        'nombre_emp', 'cif_emp', 'calle_emp', 'numero_emp', 'localidad_emp', 'cp_emp', 'provincia_emp', 'pais_emp',
                        'fcreacion', 'fmodificacion',
                    ])
                        ->where('idcliente', $id)
                        ->whereNull('fbaja')
                        ->where('idfacturacli', $invoiceId)
                        ->firstOrFail();

                    $lines = LfacturacliCentral::select([
                        'idlfacturacli', 'idfacturacli', 'idarticulo', 'codigo', 'descripcion',
                        'unidades', 'iva', 'recargo', 'pbi', 'dto',
                        'total_bi', 'total_con_impuestos', 'idalmacen', 'fcreacion',
                    ])
                        ->where('idfacturacli', $invoice->idfacturacli)
                        ->get()
                        ->map(fn ($l) => [
                            'id' => $l->idlfacturacli,
                            'article' => [
                                'id' => $l->idarticulo,
                                'code' => $l->codigo,
                                'description' => $l->descripcion,
                            ],
                            'units' => (float) $l->unidades,
                            'price_bi' => $l->pbi !== null ? (float) $l->pbi : null,
                            'discount_percent' => (float) $l->dto,
                            'tax_percent' => (float) $l->iva,
                            'surcharge_percent' => (float) $l->recargo,
                            'total_bi' => $l->total_bi !== null ? (float) $l->total_bi : null,
                            'total_with_taxes' => $l->total_con_impuestos !== null ? (float) $l->total_con_impuestos : null,
                            'warehouse' => $l->idalmacen,
                            'created' => $l->fcreacion?->format('Y-m-d H:i:s'),
                        ])->values();

                    return [
                        'id' => $invoice->idfacturacli,
                        'series' => $invoice->idserie,
                        'number' => $invoice->nfactura,
                        'year' => $invoice->anno,
                        'date' => $invoice->ffactura ? Carbon::parse($invoice->ffactura)->format('Y-m-d') : null,
                        'type' => $invoice->tipo,
                        'simplified' => (bool) $invoice->simplificada,
                        'payment_method' => $invoice->idformapago,
                        'warehouse' => $invoice->idalmacen,
                        'catalog' => $invoice->idcatalogo,
                        'debt_id' => $invoice->iddeuda,
                        'status' => $invoice->estado,
                        'observations' => $invoice->observaciones,
                        'customer' => [
                            'name' => $invoice->nombre,
                            'cif' => $invoice->cif,
                            'address' => trim(($invoice->calle ?? '').' '.($invoice->numero ?? '')),
                            'city' => $invoice->localidad,
                            'postal_code' => $invoice->cp,
                            'province' => $invoice->provincia,
                            'country' => $invoice->pais,
                        ],
                        'company' => [
                            'name' => $invoice->nombre_emp,
                            'cif' => $invoice->cif_emp,
                            'address' => trim(($invoice->calle_emp ?? '').' '.($invoice->numero_emp ?? '')),
                            'city' => $invoice->localidad_emp,
                            'postal_code' => $invoice->cp_emp,
                            'province' => $invoice->provincia_emp,
                            'country' => $invoice->pais_emp,
                        ],
                        'lines' => $lines,
                        'totals' => [
                            'lines_total_bi' => round((float) $lines->sum('total_bi'), 2),
                            'lines_total_with_taxes' => round((float) $lines->sum('total_with_taxes'), 2),
                        ],
                        'statistics' => [
                            'lines' => ['total' => $lines->count()],
                        ],
                        'created' => $invoice->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $invoice->fmodificacion?->format('Y-m-d H:i:s'),
                    ];
                }
            );

            $totalTime = microtime(true) - $startTime;

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data),
                'meta' => ['cached' => $fromCache, 'execution_time_ms' => round($totalTime * 1000, 2)],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Invoice not found for this customer'], 404);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@invoiceDetail', ['error' => $e->getMessage(), 'id' => $id, 'invoiceId' => $invoiceId]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }
}
