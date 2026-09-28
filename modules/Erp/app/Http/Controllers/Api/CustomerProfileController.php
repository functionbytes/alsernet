<?php

namespace Modules\Erp\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\Oracle\Cliente\ClientecatalogoCent;
use Modules\Erp\Models\Oracle\Cliente\ClienteCent;
use Modules\Erp\Models\Oracle\Cliente\ClientecuentaCent;
use Modules\Erp\Models\Oracle\Cliente\Clientecuota;
use Modules\Erp\Models\Oracle\Cliente\ClientetarjetaCent;
use Modules\Erp\Support\ErpErrorSanitizer;

/**
 * Ficha del cliente: identidad, LOPD, contacto, tarjetas, cuentas, catálogos y cuotas.
 *
 * Base URL: /api/erp/customer/{id}/...
 */
class CustomerProfileController extends AbstractCustomerController
{
    /**
     * Datos personales completos del cliente.
     *
     * GET /api/erp/customer/{id}/personal
     */
    public function personal(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:personal:{$id}", $id, function ($id) {
            $customer = ClienteCent::select([
                'idcliente', 'nombre', 'apellidos', 'cif', 'email', 'percontacto',
                'razonsocial', 'codigo_internet', 'idtarjeta', 'idcategoria_cliente',
                'ididioma', 'idtipocliente', 'idregfiscal', 'idregpais', 'idpaisnacionalidad',
                'estado', 'fnacimiento', 'genero', 'observaciones', 'busquedanombre',
                'oficina_contable', 'organo_gestor', 'unidad_tramitadora', 'organo_proponente',
                'fcreacion', 'fmodificacion',
            ])->whereNull('fbaja')->findOrFail($id);

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'cif' => $customer->cif,
                'email' => $customer->email,
                'contact_person' => $customer->percontacto,
                'business_name' => $customer->razonsocial,
                'code_internet' => $customer->codigo_internet,
                'card' => $customer->idtarjeta,
                'category' => $customer->idcategoria_cliente,
                'language' => $customer->ididioma,
                'customer_type' => $customer->idtipocliente,
                'fiscal_regime' => $customer->idregfiscal,
                'country_regime' => $customer->idregpais,
                'nationality' => $customer->idpaisnacionalidad,
                'gender' => $customer->genero,
                'birth_date' => $customer->fnacimiento?->format('Y-m-d'),
                'observations' => $customer->observaciones,
                'available' => $customer->estado,
                'public_administration' => [
                    'accounting_office' => $customer->oficina_contable,
                    'managing_body' => $customer->organo_gestor,
                    'processing_unit' => $customer->unidad_tramitadora,
                    'proposing_body' => $customer->organo_proponente,
                ],
                'created' => $customer->fcreacion?->format('Y-m-d H:i:s'),
                'updated' => $customer->fmodificacion?->format('Y-m-d H:i:s'),
            ];
        }, 'Personal');
    }

    /**
     * Estado y histórico LOPD del cliente.
     *
     * GET /api/erp/customer/{id}/lopd
     */
    public function lopd(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:lopd:{$id}", $id, function ($id) {
            $customer = ClienteCent::select([
                'idcliente', 'nombre', 'apellidos', 'email',
                'faceptacion_lopd', 'idusuario_aceptacion_lopd', 'origen_aceptacion_lopd',
                'idfotografia_firma_lopd', 'ubicacion_aceptacion_lopd',
                'no_informacion_comercial_lopd', 'no_datos_a_terceros_lopd',
                'tiene_interes_legitimo_lopd',
            ])->whereNull('fbaja')->findOrFail($id);

            // CLIENTE_LOPD_HIST sin índice en IDCLIENTE: full scan ~35s — se necesita índice.
            // Requiere: CREATE INDEX IDX_LOPDH_IDCLIENTE ON DEVELOPER.CLIENTE_LOPD_HIST(IDCLIENTE, FACEPTACION_LOPD);
            $history = collect();

            $historyMapped = $history->map(fn ($h) => [
                'id' => $h->idcliente_lopd_hist,
                'accepted_at' => $h->faceptacion_lopd ? (is_string($h->faceptacion_lopd) ? $h->faceptacion_lopd : $h->faceptacion_lopd->format('Y-m-d')) : null,
                'user' => $h->idusuario_aceptacion_lopd,
                'origin' => $h->origen_aceptacion_lopd,
                'signature_photo' => $h->idfotografia_firma_lopd,
                'location' => $h->ubicacion_aceptacion_lopd,
                'no_commercial_info' => (bool) $h->no_informacion_comercial_lopd,
                'no_data_to_third_parties' => (bool) $h->no_datos_a_terceros_lopd,
            ])->values();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'email' => $customer->email,
                'current' => [
                    'accepted' => $customer->faceptacion_lopd !== null,
                    'accepted_at' => $customer->faceptacion_lopd?->format('Y-m-d'),
                    'user' => $customer->idusuario_aceptacion_lopd,
                    'origin' => $customer->origen_aceptacion_lopd,
                    'signature_photo' => $customer->idfotografia_firma_lopd,
                    'location' => $customer->ubicacion_aceptacion_lopd,
                    'no_commercial_info' => (bool) $customer->no_informacion_comercial_lopd,
                    'no_data_to_third_parties' => (bool) $customer->no_datos_a_terceros_lopd,
                    'legitimate_interest' => (bool) $customer->tiene_interes_legitimo_lopd,
                ],
                'history' => $historyMapped,
                'statistics' => [
                    'history' => ['total' => $historyMapped->count()],
                ],
            ];
        }, 'Lopd');
    }

    /**
     * Direcciones del cliente.
     *
     * GET /api/erp/customer/{id}/addresses
     */
    public function addresses(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult(
                "customer:addresses:{$id}",
                function () use ($id) {
                    $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos', 'iddireccion', 'iddireccion_notif'])
                        ->with([
                            'direcciones:idclientedireccion,idcliente,idtipodireccion,calle,num,codigopostal,poblacion,provincia,pais,observacion,estado,fcreacion,fmodificacion',
                        ])
                        ->whereNull('fbaja')
                        ->findOrFail($id);

                    $defaultBilling = $customer->iddireccion;
                    $defaultShipping = $customer->iddireccion_notif;

                    $addresses = $customer->direcciones->map(fn ($d) => [
                        'id' => $d->idclientedireccion,
                        'type' => $d->idtipodireccion,
                        'street' => $d->calle,
                        'number' => $d->num,
                        'postal_code' => $d->codigopostal,
                        'city' => $d->poblacion,
                        'province' => $d->provincia,
                        'country' => $d->pais,
                        'observations' => $d->observacion,
                        'available' => $d->estado,
                        'default_billing' => $d->idclientedireccion === $defaultBilling,
                        'default_shipping' => $d->idclientedireccion === $defaultShipping,
                        'created' => $d->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $d->fmodificacion?->format('Y-m-d H:i:s'),
                    ])->values();

                    return [
                        'id' => $customer->idcliente,
                        'label' => $customer->nombre,
                        'surnames' => $customer->apellidos,
                        'addresses' => $addresses,
                        'statistics' => [
                            'addresses' => ['total' => $addresses->count()],
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
            return response()->json(['success' => false, 'error' => 'Customer not found'], 404);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@addresses', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }

    /**
     * Contacto: emails + teléfonos.
     *
     * GET /api/erp/customer/{id}/contact
     */
    public function contact(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult(
                "customer:contact:{$id}",
                function () use ($id) {
                    $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos', 'email', 'percontacto'])
                        ->with([
                            'telefonos:idclientetelefono,idcliente,idtipotelefono,idprefijo_telefono,telefono,horario,observacion,envio_sms,estado,fcreacion,fmodificacion',
                        ])
                        ->whereNull('fbaja')
                        ->findOrFail($id);

                    $phones = $customer->telefonos->map(fn ($t) => [
                        'id' => $t->idclientetelefono,
                        'type' => $t->idtipotelefono,
                        'prefix' => $t->idprefijo_telefono,
                        'number' => $t->telefono,
                        'schedule' => $t->horario,
                        'sms_enabled' => (bool) $t->envio_sms,
                        'observations' => $t->observacion,
                        'available' => $t->estado,
                        'created' => $t->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $t->fmodificacion?->format('Y-m-d H:i:s'),
                    ])->values();

                    return [
                        'id' => $customer->idcliente,
                        'label' => $customer->nombre,
                        'surnames' => $customer->apellidos,
                        'email' => $customer->email,
                        'contact_person' => $customer->percontacto,
                        'phones' => $phones,
                        'statistics' => [
                            'phones' => ['total' => $phones->count()],
                            'emails' => ['total' => $customer->email ? 1 : 0],
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
            return response()->json(['success' => false, 'error' => 'Customer not found'], 404);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@contact', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }

    /**
     * Tarjetas del cliente.
     *
     * GET /api/erp/customer/{id}/cards
     */
    public function cards(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:cards:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos', 'idtarjeta'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $cards = ClientetarjetaCent::select([
                'idclientetarjeta', 'idcliente', 'idtarjeta', 'numerotarjeta',
                'idbanco', 'nombretitular', 'fcaducidad', 'limite', 'idmoneda',
                'observacion', 'estado', 'fcreacion', 'fmodificacion',
            ])
                ->where('idcliente', $id)
                ->whereNull('fbaja')
                ->orderByDesc('fcreacion')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->idclientetarjeta,
                    'card_id' => $c->idtarjeta,
                    'number' => $c->numerotarjeta,
                    'bank' => $c->idbanco,
                    'holder' => $c->nombretitular,
                    'expires' => $c->fcaducidad?->format('Y-m-d'),
                    'limit' => $c->limite !== null ? (float) $c->limite : null,
                    'currency' => $c->idmoneda,
                    'observations' => $c->observacion,
                    'available' => $c->estado,
                    'is_main' => $c->idtarjeta === $customer->idtarjeta,
                    'created' => $c->fcreacion?->format('Y-m-d H:i:s'),
                    'updated' => $c->fmodificacion?->format('Y-m-d H:i:s'),
                ])->values();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'main_card' => $customer->idtarjeta,
                'cards' => $cards,
                'statistics' => [
                    'cards' => ['total' => $cards->count()],
                ],
            ];
        }, 'Cards');
    }

    /**
     * Cuentas bancarias del cliente.
     *
     * GET /api/erp/customer/{id}/accounts
     */
    public function accounts(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:accounts:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $accounts = ClientecuentaCent::select([
                'idclientecuenta', 'idcliente', 'idbanco', 'iban', 'bic',
                'entidad_', 'oficina_', 'control_', 'ncuenta_',
                'observacion', 'estado', 'fcreacion', 'fmodificacion',
            ])
                ->where('idcliente', $id)
                ->whereNull('fbaja')
                ->orderByDesc('fcreacion')
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->idclientecuenta,
                    'bank' => $a->idbanco,
                    'iban' => $this->maskIban($a->iban),
                    'bic' => $a->bic,
                    'legacy' => [
                        'entity' => $a->entidad_,
                        'office' => $a->oficina_,
                        'control' => $a->control_,
                        'number' => $a->ncuenta_ ? '****'.substr($a->ncuenta_, -4) : null,
                    ],
                    'observations' => $a->observacion,
                    'available' => $a->estado,
                    'created' => $a->fcreacion?->format('Y-m-d H:i:s'),
                    'updated' => $a->fmodificacion?->format('Y-m-d H:i:s'),
                ])->values();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'accounts' => $accounts,
                'statistics' => [
                    'accounts' => ['total' => $accounts->count()],
                ],
            ];
        }, 'Accounts');
    }

    /**
     * Catálogos suscritos por el cliente.
     *
     * GET /api/erp/customer/{id}/catalogs
     */
    public function catalogs(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:catalogs:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $catalogs = ClientecatalogoCent::select([
                'idclientecatalogo', 'idcliente', 'idcatalogo',
                'estado', 'fsuscripcion', 'fcreacion', 'fmodificacion', 'fbaja',
            ])
                ->where('idcliente', $id)
                ->orderByDesc('fsuscripcion')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->idclientecatalogo,
                    'catalog_id' => $c->idcatalogo,
                    'available' => $c->estado,
                    'subscribed_at' => $c->fsuscripcion?->format('Y-m-d H:i:s'),
                    'unsubscribed_at' => $c->fbaja?->format('Y-m-d H:i:s'),
                    'created' => $c->fcreacion?->format('Y-m-d H:i:s'),
                    'updated' => $c->fmodificacion?->format('Y-m-d H:i:s'),
                ])->values();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'catalogs' => $catalogs,
                'statistics' => [
                    'catalogs' => [
                        'total' => $catalogs->count(),
                        'active' => $catalogs->where('available', true)->count(),
                    ],
                ],
            ];
        }, 'Catalogs');
    }

    /**
     * Cuotas / membresías del cliente.
     *
     * GET /api/erp/customer/{id}/quotas
     */
    public function quotas(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:quotas:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $now = now();

            $quotas = Clientecuota::select([
                'idclientecuota', 'idcliente', 'idarticulo', 'idalmacen',
                'idclientecuenta', 'fcontratacion', 'ffinservicio', 'importe',
                'estado', 'fcreacion', 'fmodificacion',
            ])
                ->where('idcliente', $id)
                ->whereNull('fbaja')
                ->orderByDesc('fcontratacion')
                ->get()
                ->map(function ($q) use ($now) {
                    $end = $q->ffinservicio ? Carbon::parse($q->ffinservicio) : null;
                    $isActive = $q->estado && (! $end || $end->gte($now));

                    return [
                        'id' => $q->idclientecuota,
                        'article' => $q->idarticulo,
                        'warehouse' => $q->idalmacen,
                        'account' => $q->idclientecuenta,
                        'amount' => $q->importe !== null ? (float) $q->importe : null,
                        'contract_date' => $q->fcontratacion ? Carbon::parse($q->fcontratacion)->format('Y-m-d') : null,
                        'end_date' => $end?->format('Y-m-d'),
                        'is_active' => $isActive,
                        'available' => $q->estado,
                        'created' => $q->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $q->fmodificacion?->format('Y-m-d H:i:s'),
                    ];
                })->values();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'quotas' => $quotas,
                'statistics' => [
                    'quotas' => [
                        'total' => $quotas->count(),
                        'active' => $quotas->where('is_active', true)->count(),
                        'amount_total' => round((float) $quotas->sum('amount'), 2),
                    ],
                ],
            ];
        }, 'Quotas');
    }
}
