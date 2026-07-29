<?php

namespace Modules\Erp\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Erp\Models\Oracle\Albaran\AlbarancliCentral;
use Modules\Erp\Models\Oracle\Albaran\LalbarancliCentral;
use Modules\Erp\Models\Oracle\Cliente\ClientecatalogoCent;
use Modules\Erp\Models\Oracle\Cliente\ClienteCent;
use Modules\Erp\Models\Oracle\Cliente\ClientecuentaCent;
use Modules\Erp\Models\Oracle\Cliente\Clientecuota;
use Modules\Erp\Models\Oracle\Cliente\ClientetarjetaCent;
use Modules\Erp\Models\Oracle\Cobro\CobrocliCentral;
use Modules\Erp\Models\Oracle\Cobro\DeudacliCentral;
use Modules\Erp\Models\Oracle\Factura\FacturacliCentral;
use Modules\Erp\Models\Oracle\Factura\LfacturacliCentral;
use Modules\Erp\Models\Oracle\Otros\Puntofidelizacion;
use Modules\Erp\Models\Oracle\Otros\Vale;
use Modules\Erp\Models\Oracle\Pedido\PedidocliCentral;
use Modules\Erp\Models\Oracle\Promocion\BonoPromocion;
use Modules\Erp\Services\HelpdeskWebhookSender;
use Modules\Erp\Services\OCI8Service;

/**
 * CUSTOMER API (Helpdesk / CallCenter)
 *
 * Endpoints granulares en inglés para uso de agentes que consultan
 * la ficha del cliente. Naming alineado con SuppliersController.
 *
 * Base URL: /api/erp/customer
 */
class CustomerController extends ApiController
{
    /**
     * Lista paginada de clientes con filtros completos.
     *
     * GET /api/erp/customer?{filters}
     *
     * Filters: id, cif, email, surnames, phone, birth_date,
     * lopd_from, lopd_to, deleted_from, deleted_to
     * Pagination: limit (max 100), offset
     */
    public function list(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);

            $conditions = ['t.FBAJA IS NULL'];
            $bindings = [];

            if ($request->filled('id')) {
                $conditions[] = 't.IDCLIENTE = ?';
                $bindings[] = $request->get('id');
            }
            if ($request->filled('cif')) {
                $conditions[] = 't.CIF = ?';
                $bindings[] = $request->get('cif');
            }
            if ($request->filled('email')) {
                $conditions[] = 't.EMAIL = ?';
                $bindings[] = $request->get('email');
            }
            if ($request->filled('surnames')) {
                $conditions[] = 'UPPER(t.APELLIDOS) LIKE ?';
                $bindings[] = strtoupper((string) $request->get('surnames')).'%';
            }
            if ($request->filled('phone')) {
                $conditions[] = 't.IDCLIENTE IN (SELECT IDCLIENTE FROM DEVELOPER.CLIENTETELEFONO_CENT WHERE TELEFONO = ?)';
                $bindings[] = $request->get('phone');
            }
            if ($request->filled('birth_date')) {
                $conditions[] = "TRUNC(t.FNACIMIENTO) = TO_DATE(?, 'YYYY-MM-DD')";
                $bindings[] = $request->get('birth_date');
            }
            if ($request->filled('lopd_from')) {
                $conditions[] = 't.FACEPTACION_LOPD >= ?';
                $bindings[] = $request->get('lopd_from');
            }
            if ($request->filled('lopd_to')) {
                $conditions[] = 't.FACEPTACION_LOPD <= ?';
                $bindings[] = $request->get('lopd_to');
            }
            if ($request->filled('deleted_from')) {
                $conditions[] = 't.FBAJA >= ?';
                $bindings[] = $request->get('deleted_from');
            }
            if ($request->filled('deleted_to')) {
                $conditions[] = 't.FBAJA <= ?';
                $bindings[] = $request->get('deleted_to');
            }

            $where = 'WHERE '.implode(' AND ', $conditions);
            $cols = 't.IDCLIENTE, t.NOMBRE, t.APELLIDOS, t.CIF, t.EMAIL, '.
                    't.CODIGO_INTERNET, t.IDTARJETA, t.IDCATEGORIA_CLIENTE, t.IDIDIOMA, t.ESTADO, '.
                    "TO_CHAR(t.FACEPTACION_LOPD, 'YYYY-MM-DD') AS FACEPTACION_LOPD, ".
                    't.NO_INFORMACION_COMERCIAL_LOPD, t.NO_DATOS_A_TERCEROS_LOPD, t.TIENE_INTERES_LEGITIMO_LOPD, '.
                    "TO_CHAR(t.FCREACION, 'YYYY-MM-DD HH24:MI:SS') AS FCREACION, ".
                    "TO_CHAR(t.FMODIFICACION, 'YYYY-MM-DD HH24:MI:SS') AS FMODIFICACION, ".
                    "TO_CHAR(t.FBAJA, 'YYYY-MM-DD') AS FBAJA";

            $rownum = $limit + 1;

            if ($offset === 0) {
                $sql = "SELECT /*+ FIRST_ROWS({$limit}) */ {$cols} FROM DEVELOPER.CLIENTE_CENT t {$where} AND ROWNUM <= {$rownum}";
            } else {
                $totalLimit = $offset + $limit + 1;
                $sql = 'SELECT * FROM (SELECT t2.*, ROWNUM AS rn FROM ('.
                       "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT t {$where}".
                       ") t2 WHERE ROWNUM <= {$totalLimit}) WHERE rn > {$offset}";
            }

            $rows = DB::connection('oracle')->select($sql, $bindings);
            $hasMore = count($rows) > $limit;
            if ($hasMore) {
                $rows = array_slice($rows, 0, $limit);
            }

            $data = array_map(fn ($c) => [
                'id' => $c->idcliente,
                'label' => $c->nombre,
                'surnames' => $c->apellidos,
                'cif' => $c->cif,
                'email' => $c->email,
                'card' => $c->idtarjeta,
                'code_internet' => $c->codigo_internet,
                'category' => $c->idcategoria_cliente,
                'language' => $c->ididioma,
                'available' => (bool) $c->estado,
                'lopd' => [
                    'accepted' => $c->faceptacion_lopd !== null,
                    'accepted_at' => $c->faceptacion_lopd,
                    'no_commercial_info' => (bool) $c->no_informacion_comercial_lopd,
                    'no_data_to_third_parties' => (bool) $c->no_datos_a_terceros_lopd,
                    'legitimate_interest' => (bool) $c->tiene_interes_legitimo_lopd,
                ],
                'created' => $c->fcreacion,
                'updated' => $c->fmodificacion,
                'deleted' => $c->fbaja,
            ], $rows);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Customer List: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data),
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => count($rows),
                    'hasMore' => $hasMore,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@list', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 500);
        }
    }

    /**
     * Crear o actualizar cliente.
     *
     * POST /api/erp/customer
     *
     * Body: id (opcional para update), name, surnames, cif, email,
     * lopd_accepted_at, no_commercial_info, no_data_to_third_parties,
     * catalogs (CSV o array), [contact_person, observations, language, gender, birth_date]
     */
    public function create(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        DB::connection('oracle')->beginTransaction();

        try {
            $request->validate([
                // `id` is treated as an UPDATE target. Validate it actually exists
                // in cliente_cent so an unauthenticated caller can't blind-write
                // to an arbitrary id by guessing.
                'id' => 'sometimes|integer|min:1',
                'name' => 'required|string',
                'surnames' => 'required|string',
                'cif' => 'required|string',
                'email' => 'required|email',
                'lopd_accepted_at' => 'required|date',
                'no_commercial_info' => 'required|boolean',
                'no_data_to_third_parties' => 'required|boolean',
                'catalogs' => 'required',
            ]);

            if ($request->filled('id')) {
                $customer = ClienteCent::find($request->integer('id'));
                if (! $customer) {
                    DB::connection('oracle')->rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Cliente no encontrado',
                    ], 404);
                }
            } else {
                $customer = new ClienteCent;
            }

            $customer->nombre = $request->input('name');
            $customer->apellidos = $request->input('surnames');
            $customer->cif = $request->input('cif');
            $customer->email = $request->input('email');
            $customer->faceptacion_lopd = $request->input('lopd_accepted_at');
            $customer->no_informacion_comercial_lopd = $request->boolean('no_commercial_info');
            $customer->no_datos_a_terceros_lopd = $request->boolean('no_data_to_third_parties');

            if ($request->filled('contact_person')) {
                $customer->percontacto = $request->input('contact_person');
            }
            if ($request->filled('observations')) {
                $customer->observaciones = $request->input('observations');
            }
            if ($request->filled('language')) {
                $customer->ididioma = $request->input('language');
            }
            if ($request->filled('gender')) {
                $customer->genero = $request->input('gender');
            }
            if ($request->filled('birth_date')) {
                $customer->fnacimiento = $request->input('birth_date');
            }

            $customer->save();

            $catalogs = $request->input('catalogs');
            $catalogIds = is_array($catalogs) ? $catalogs : array_map('trim', explode(',', (string) $catalogs));
            $catalogIds = array_filter($catalogIds, fn ($v) => $v !== '');

            foreach ($catalogIds as $catalogId) {
                ClientecatalogoCent::firstOrCreate([
                    'idcliente' => $customer->idcliente,
                    'idcatalogo' => $catalogId,
                ], [
                    'estado' => 1,
                    'fsuscripcion' => now(),
                ]);
            }

            DB::connection('oracle')->commit();
            $this->forgetCache((int) $customer->idcliente);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Customer Create: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json([
                'success' => true,
                'data' => ['id' => $customer->idcliente],
            ], 201, [], JSON_UNESCAPED_UNICODE);

        } catch (ValidationException $e) {
            DB::connection('oracle')->rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::connection('oracle')->rollBack();
            Log::error('Error CustomerController@create', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 500);
        }
    }

    /**
     * Actualizar consentimiento LOPD por email.
     *
     * PATCH /api/erp/customer/lopd
     *
     * Body: email, accepted_at, no_commercial_info, no_data_to_third_parties
     */
    public function updateLopd(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $request->validate([
                'email' => 'required|email',
                'accepted_at' => 'required|date',
                'no_commercial_info' => 'required|boolean',
                'no_data_to_third_parties' => 'required|boolean',
            ]);

            $customer = ClienteCent::where('email', $request->input('email'))->first();

            if (! $customer) {
                return response()->json([
                    'success' => false,
                    'error' => 'Customer not found',
                ], 404);
            }

            $customer->faceptacion_lopd = $request->input('accepted_at');
            $customer->no_informacion_comercial_lopd = $request->boolean('no_commercial_info');
            $customer->no_datos_a_terceros_lopd = $request->boolean('no_data_to_third_parties');
            $customer->save();

            $this->forgetCache((int) $customer->idcliente);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Customer UpdateLopd: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json([
                'success' => true,
                'data' => ['id' => $customer->idcliente],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@updateLopd', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 500);
        }
    }

    /**
     * Búsqueda unificada de clientes (DNI, email, teléfono, tarjeta, código internet, apellidos).
     *
     * GET /api/erp/customer/search?q=...&limit=10
     */
    public function search(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $q = trim((string) $request->get('q', ''));
            $limit = min((int) $request->get('limit', 10), 50);

            if ($q === '') {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'pagination' => ['limit' => $limit, 'offset' => 0, 'count' => 0, 'hasMore' => false],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            $oci8 = app(OCI8Service::class);
            $n = $limit + 1;
            $cols = 'IDCLIENTE, NOMBRE, APELLIDOS, CIF, EMAIL, CODIGO_INTERNET, IDTARJETA, ESTADO, '.
                    "TO_CHAR(FCREACION, 'YYYY-MM-DD HH24:MI:SS') AS FCREACION, ".
                    "TO_CHAR(FMODIFICACION, 'YYYY-MM-DD HH24:MI:SS') AS FMODIFICACION";

            // Heurística de tipo de búsqueda:
            // • Contiene '@'   → email exacto (sin índice: scan completo, lento si no hay coincidencia)
            // • Solo dígitos   → IDCLIENTE, IDTARJETA, CODIGO_INTERNET (indexados) + teléfono (≥6 dígitos)
            // • Texto          → CIF exacto (indexado) → CIF uppercase → APELLIDOS LIKE → NOMBRE LIKE
            // Timeout de 10s para búsquedas sin índice — evita bloquear workers de PHP-FPM
            $slowTimeout = 10000;

            if (str_contains($q, '@')) {
                // Búsqueda por email exacto — sin índice → scan completo. Requiere:
                // CREATE INDEX IDX_CLIENTE_CENT_EMAIL ON DEVELOPER.CLIENTE_CENT(UPPER(EMAIL))
                $rows = $oci8->query(
                    "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE FBAJA IS NULL AND UPPER(EMAIL) = :q AND ROWNUM <= {$n}",
                    ['q' => strtoupper($q)],
                    $slowTimeout
                );
            } elseif (ctype_digit($q)) {
                // Columnas indexadas: usa literales para ROWNUM (stopkey en tiempo de compilación)
                $rows = $oci8->query(
                    'SELECT * FROM ('.
                    "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE FBAJA IS NULL AND IDCLIENTE = :q AND ROWNUM <= {$n} ".
                    'UNION ALL '.
                    "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE FBAJA IS NULL AND IDTARJETA = :q AND ROWNUM <= {$n} ".
                    'UNION ALL '.
                    "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE FBAJA IS NULL AND CODIGO_INTERNET = :q AND ROWNUM <= {$n} ".
                    ") WHERE ROWNUM <= {$n}",
                    ['q' => $q]
                );

                // Teléfono: busca en CLIENTETELEFONO_CENT si la cadena tiene al menos 6 dígitos.
                // Sin índice en TELEFONO → scan completo. Requiere:
                // CREATE INDEX IDX_CLIENTETELEFONO_TELEFONO ON DEVELOPER.CLIENTETELEFONO_CENT(TELEFONO)
                if (empty($rows) && strlen($q) >= 6) {
                    $rows = $oci8->query(
                        "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE FBAJA IS NULL AND IDCLIENTE IN (".
                        "SELECT IDCLIENTE FROM DEVELOPER.CLIENTETELEFONO_CENT WHERE TELEFONO = :q AND ROWNUM <= {$n}".
                        ") AND ROWNUM <= {$n}",
                        ['q' => $q],
                        $slowTimeout
                    );
                }
            } else {
                // CIF exacto con índice (IDX_CLIENTE_CENT_CIF) — O(log n), muy rápido
                $rows = $oci8->query(
                    "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE FBAJA IS NULL AND CIF = :q AND ROWNUM <= {$n}",
                    ['q' => $q]
                );

                // CIF sin distinguir mayúsculas (IDX_CLIENTE_CENT_CIF_UPPER) — O(log n)
                if (empty($rows)) {
                    $rows = $oci8->query(
                        "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE FBAJA IS NULL AND UPPER(CIF) = :q AND ROWNUM <= {$n}",
                        ['q' => strtoupper($q)]
                    );
                }

                // Fallback LIKE en apellidos — full scan pero para al encontrar n filas
                if (empty($rows)) {
                    $rows = $oci8->query(
                        "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE FBAJA IS NULL AND UPPER(APELLIDOS) LIKE :srch AND ROWNUM <= {$n}",
                        ['srch' => strtoupper($q).'%']
                    );
                }

                // Fallback LIKE en nombre
                if (empty($rows)) {
                    $rows = $oci8->query(
                        "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE FBAJA IS NULL AND UPPER(NOMBRE) LIKE :srch AND ROWNUM <= {$n}",
                        ['srch' => strtoupper($q).'%']
                    );
                }
            }

            $hasMore = count($rows) > $limit;
            if ($hasMore) {
                $rows = array_slice($rows, 0, $limit);
            }

            $data = array_map(fn ($c) => [
                'id' => $c['idcliente'],
                'label' => $c['nombre'],
                'surnames' => $c['apellidos'],
                'cif' => $c['cif'],
                'email' => $c['email'],
                'card' => $c['idtarjeta'],
                'code_internet' => $c['codigo_internet'],
                'available' => (bool) ($c['estado'] ?? false),
                'created' => $c['fcreacion'],
                'updated' => $c['fmodificacion'],
            ], $rows);

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Customer Search: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data),
                'pagination' => [
                    'limit' => $limit,
                    'offset' => 0,
                    'count' => count($rows),
                    'hasMore' => $hasMore,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@search', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Busca un cliente en el ERP por su ID web, con fallback a email e idcliente.
     *
     * GET /api/erp/customer/search/web/{idweb}?email=X&id=Y
     *
     * Orden de búsqueda:
     *   1. CODIGO_INTERNET = idweb  (índice IDX_CLIENTE_CENT_CODIGO_INT)
     *   2. EMAIL = ?email            (si se pasa y no se encuentra en paso 1)
     *   3. IDCLIENTE = ?id           (si se pasa y no se encuentra en paso 2)
     *
     * Incluye clientes dados de baja (FBAJA no nulo).
     * El campo `matched_by` indica qué campo resolvió la búsqueda.
     */
    public function findByIdWeb(Request $request, int $idweb): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $oci8 = app(OCI8Service::class);

            $cols = 'IDCLIENTE, NOMBRE, APELLIDOS, CIF, EMAIL, CODIGO_INTERNET, IDTARJETA, '.
                    "ESTADO, TO_CHAR(FCREACION, 'YYYY-MM-DD HH24:MI:SS') AS FCREACION, ".
                    "TO_CHAR(FMODIFICACION, 'YYYY-MM-DD HH24:MI:SS') AS FMODIFICACION, ".
                    "TO_CHAR(FBAJA, 'YYYY-MM-DD') AS FBAJA";

            $rows = [];
            $matchedBy = null;

            // 1. Por CODIGO_INTERNET (idweb)
            $rows = $oci8->query(
                "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE CODIGO_INTERNET = :idweb AND ROWNUM <= 1",
                ['idweb' => $idweb]
            );
            if (! empty($rows)) {
                $matchedBy = 'idweb';
            }

            // 2. Por EMAIL
            if (empty($rows) && $request->filled('email')) {
                $email = trim((string) $request->get('email'));
                $rows = $oci8->query(
                    "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE UPPER(EMAIL) = :email AND ROWNUM <= 1",
                    ['email' => strtoupper($email)]
                );
                if (! empty($rows)) {
                    $matchedBy = 'email';
                }
            }

            // 3. Por IDCLIENTE
            if (empty($rows) && $request->filled('id')) {
                $idcliente = (int) $request->get('id');
                $rows = $oci8->query(
                    "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE IDCLIENTE = :id AND ROWNUM <= 1",
                    ['id' => $idcliente]
                );
                if (! empty($rows)) {
                    $matchedBy = 'id';
                }
            }

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Customer findByIdWeb: '.round($totalTime * 1000, 2).'ms, matched_by='.($matchedBy ?? 'none').' ===');

            if (empty($rows)) {
                return response()->json([
                    'success' => true,
                    'exists' => false,
                    'data' => null,
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            $c = $rows[0];

            return response()->json([
                'success'    => true,
                'exists'     => true,
                'matched_by' => $matchedBy,
                'data'       => $this->cleanUtf8Array([
                    'id'            => $c['idcliente'],
                    'label'         => $c['nombre'],
                    'surnames'      => $c['apellidos'],
                    'cif'           => $c['cif'],
                    'email'         => $c['email'],
                    'code_internet' => $c['codigo_internet'],
                    'card'          => $c['idtarjeta'],
                    'available'     => (bool) ($c['estado'] ?? false),
                    'deleted_at'    => $c['fbaja'],
                    'created'       => $c['fcreacion'],
                    'updated'       => $c['fmodificacion'],
                ]),
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@findByIdWeb', ['idweb' => $idweb, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $this->sanitizeOracleError($e->getMessage()),
            ], 500);
        }
    }

    /**
     * Resumen ficha helpdesk (cabecera).
     *
     * GET /api/erp/customer/{id}
     */
    public function summary(int $id): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult(
                "customer:summary:{$id}",
                function () use ($id) {
                    $customer = ClienteCent::select([
                        'idcliente', 'nombre', 'apellidos', 'cif', 'email',
                        'codigo_internet', 'idtarjeta', 'idcategoria_cliente', 'ididioma',
                        'estado', 'fnacimiento', 'genero', 'observaciones',
                        'faceptacion_lopd', 'no_informacion_comercial_lopd',
                        'no_datos_a_terceros_lopd', 'tiene_interes_legitimo_lopd',
                        'fcreacion', 'fmodificacion',
                    ])
                        ->with([
                            'telefonos:idclientetelefono,idcliente,idtipotelefono,idprefijo_telefono,telefono,estado',
                            'direcciones:idclientedireccion,idcliente,idtipodireccion,calle,num,codigopostal,poblacion,provincia,pais,estado',
                        ])
                        ->whereNull('fbaja')
                        ->findOrFail($id);

                    try {
                        $customer->load('tarjetas:idclientetarjeta,idcliente,idtarjeta,numerotarjeta,nombretitular,fcaducidad,estado');
                    } catch (\Exception) {
                        $customer->setRelation('tarjetas', collect());
                    }

                    // PEDIDOCLI_CENTRAL no tiene índice en IDCLIENTE: cualquier query requiere full scan (~35s).
                    // Los pedidos se cargan de forma diferida vía GET /orders.
                    $ordersCount = null;
                    $lastOrder = null;

                    return [
                        'id' => $customer->idcliente,
                        'label' => $customer->nombre,
                        'surnames' => $customer->apellidos,
                        'cif' => $customer->cif,
                        'email' => $customer->email,
                        'code_internet' => $customer->codigo_internet,
                        'card' => $customer->idtarjeta,
                        'language' => $customer->ididioma,
                        'category' => $customer->idcategoria_cliente,
                        'gender' => $customer->genero,
                        'birth_date' => $customer->fnacimiento?->format('Y-m-d'),
                        'observations' => $customer->observaciones,
                        'available' => $customer->estado,
                        'created' => $customer->fcreacion?->format('Y-m-d H:i:s'),
                        'updated' => $customer->fmodificacion?->format('Y-m-d H:i:s'),

                        'phones' => $customer->telefonos->map(fn ($t) => [
                            'id' => $t->idclientetelefono,
                            'number' => $t->telefono,
                            'type' => $t->idtipotelefono,
                            'prefix' => $t->idprefijo_telefono,
                            'available' => $t->estado,
                        ])->values(),

                        'addresses' => $customer->direcciones->map(fn ($d) => [
                            'id' => $d->idclientedireccion,
                            'type' => $d->idtipodireccion,
                            'street' => $d->calle,
                            'number' => $d->num,
                            'postal_code' => $d->codigopostal,
                            'city' => $d->poblacion,
                            'province' => $d->provincia,
                            'country' => $d->pais,
                            'available' => $d->estado,
                        ])->values(),

                        'cards' => $customer->tarjetas->map(fn ($t) => [
                            'id' => $t->idclientetarjeta,
                            'card_id' => $t->idtarjeta,
                            'number' => $t->numerotarjeta,
                            'holder' => $t->nombretitular,
                            'expires' => $t->fcaducidad?->format('Y-m-d'),
                            'available' => $t->estado,
                        ])->values(),

                        'last_order' => $lastOrder ? [
                            'id' => $lastOrder['idpedidocli_central'],
                            'order_id' => $lastOrder['idpedidocli'],
                            'number' => $lastOrder['npedidocli'],
                            'status' => $lastOrder['estado'],
                            'date' => $lastOrder['fpedido'],
                            'expected_date' => $lastOrder['fprevista'],
                            'served_date' => $lastOrder['fservido'],
                            'observations' => $lastOrder['observaciones'],
                        ] : null,

                        'lopd' => [
                            'accepted' => $customer->faceptacion_lopd !== null,
                            'accepted_at' => $customer->faceptacion_lopd?->format('Y-m-d'),
                            'no_commercial_info' => (bool) $customer->no_informacion_comercial_lopd,
                            'no_data_to_third_parties' => (bool) $customer->no_datos_a_terceros_lopd,
                            'legitimate_interest' => (bool) $customer->tiene_interes_legitimo_lopd,
                        ],

                        'statistics' => [
                            'orders' => ['total' => $ordersCount],
                            'phones' => ['total' => $customer->telefonos->count()],
                            'addresses' => ['total' => $customer->direcciones->count()],
                            'cards' => ['total' => $customer->tarjetas->count()],
                        ],
                    ];
                }
            );

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Customer Summary: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

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
                'error' => 'Customer not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@summary', [
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
            Log::debug('=== TIEMPO Customer Addresses: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

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

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 500);
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
            Log::debug('=== TIEMPO Customer Contact: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

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

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 500);
        }
    }

    /**
     * Lista paginada de pedidos del cliente (cabecera).
     *
     * GET /api/erp/customer/{id}/orders?limit=10&offset=0&status=&from=&to=
     */
    public function orders(int $id, Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);
            $status = (string) $request->get('status', '');
            $from = (string) $request->get('from', '');
            $to = (string) $request->get('to', '');

            $cacheKey = "customer:orders:{$id}:s{$status}:f{$from}:t{$to}:off{$offset}:lim{$limit}";
            $cached = cache()->get($cacheKey);

            if ($cached !== null) {
                $rows = $cached;
                $hasMore = count($rows) > $limit;
                if ($hasMore) {
                    $rows = array_slice($rows, 0, $limit);
                }
                $totalTime = microtime(true) - $startTime;
                Log::debug('=== TIEMPO Customer Orders: '.round($totalTime * 1000, 2).'ms (Cache: HIT) ===');

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
            $conditions = ['IDCLIENTE = :id', 'FBAJA IS NULL'];
            $bindings = ['id' => $id];

            if ($status !== '') {
                $conditions[] = 'ESTADO = :status';
                $bindings['status'] = $status;
            }
            if ($from !== '') {
                $conditions[] = "FPEDIDO >= TO_DATE(:from, 'YYYY-MM-DD')";
                $bindings['from'] = $from;
            }
            if ($to !== '') {
                $conditions[] = "FPEDIDO <= TO_DATE(:to, 'YYYY-MM-DD')";
                $bindings['to'] = $to;
            }

            $where = 'WHERE '.implode(' AND ', $conditions);
            $cols = 'IDPEDIDOCLI_CENTRAL, IDPEDIDOCLI, IDCLIENTE, IDALMACEN, ESTADO, NPEDIDOCLI, '.
                    "TO_CHAR(FPEDIDO, 'YYYY-MM-DD HH24:MI:SS') AS FPEDIDO, ".
                    "TO_CHAR(FPREVISTA, 'YYYY-MM-DD') AS FPREVISTA, ".
                    "TO_CHAR(FSERVIDO, 'YYYY-MM-DD HH24:MI:SS') AS FSERVIDO, ".
                    'OBSERVACIONES, TIPOPEDIDO, IDORIGENPEDIDOCLI, '.
                    "TO_CHAR(FCREACION, 'YYYY-MM-DD HH24:MI:SS') AS FCREACION, ".
                    "TO_CHAR(FMODIFICACION, 'YYYY-MM-DD HH24:MI:SS') AS FMODIFICACION";

            $totalLimit = $offset + $limit + 1;
            $sql = $offset === 0
                ? "SELECT {$cols} FROM DEVELOPER.PEDIDOCLI_CENTRAL {$where} AND ROWNUM <= {$totalLimit}"
                : 'SELECT * FROM (SELECT t1.*, ROWNUM AS rn FROM ('.
                  "SELECT {$cols} FROM DEVELOPER.PEDIDOCLI_CENTRAL {$where}".
                  ") t1 WHERE ROWNUM <= {$totalLimit}) WHERE rn > {$offset}";

            // Background load — guarded by a Redis lock so a stampede of concurrent
            // requests for the same customer can't all fire the 35s full-scan in
            // parallel and saturate the PHP-FPM pool.
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

                        // Notificar al system Helpdesk que los pedidos están listos
                        try {
                            $email = (string) DB::connection('oracle')
                                ->table('DEVELOPER.CLIENTE_CENT')
                                ->where('IDCLIENTE', $id)
                                ->value('EMAIL');
                            if ($email) {
                                app(HelpdeskWebhookSender::class)->notifyOrdersReady($email, $id);
                            }
                        } catch (\Throwable $e) {
                            Log::info('Helpdesk webhook not sent', ['id' => $id, 'error' => $e->getMessage()]);
                        }
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

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Customer Orders: '.round($totalTime * 1000, 2).'ms (Cache: MISS → shutdown_fn) ===');

            return response()->json([
                'success' => true,
                'data' => [],
                'pagination' => ['limit' => $limit, 'offset' => $offset, 'count' => 0, 'hasMore' => false],
                'meta' => ['cached' => false, 'available' => false, 'loading' => true, 'retry_after' => 35],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@orders', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 500);
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
            Log::debug('=== TIEMPO Customer Order Detail: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

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

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 500);
        }
    }

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

    /**
     * Albaranes del cliente (paginado).
     *
     * GET /api/erp/customer/{id}/delivery-notes?limit=10&offset=0&from=&to=
     */
    public function deliveryNotes(int $id, Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);

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

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Customer DeliveryNotes: '.round($totalTime * 1000, 2).'ms ===');

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

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 500);
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
            Log::debug('=== TIEMPO Customer DeliveryNote Detail: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data),
                'meta' => ['cached' => $fromCache, 'execution_time_ms' => round($totalTime * 1000, 2)],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Delivery note not found for this customer'], 404);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@deliveryNoteDetail', ['error' => $e->getMessage(), 'id' => $id, 'deliveryId' => $deliveryId]);

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 500);
        }
    }

    /**
     * Facturas del cliente (paginado).
     *
     * GET /api/erp/customer/{id}/invoices?limit=10&offset=0&year=&from=&to=
     */
    public function invoices(int $id, Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);

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

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Customer Invoices: '.round($totalTime * 1000, 2).'ms ===');

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

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 200);
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
            Log::debug('=== TIEMPO Customer Invoice Detail: '.round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data),
                'meta' => ['cached' => $fromCache, 'execution_time_ms' => round($totalTime * 1000, 2)],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Invoice not found for this customer'], 404);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@invoiceDetail', ['error' => $e->getMessage(), 'id' => $id, 'invoiceId' => $invoiceId]);

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 500);
        }
    }

    /**
     * Cobros del cliente (paginado).
     *
     * GET /api/erp/customer/{id}/payments?limit=10&offset=0&from=&to=
     */
    public function payments(int $id, Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $limit = min((int) $request->get('limit', 10), 100);
            $offset = (int) $request->get('offset', 0);

            $query = CobrocliCentral::select([
                'idcobrocli_central', 'idcobrocli', 'idcliente', 'idformapago',
                'idtransportista', 'idvale', 'idcaja', 'estado',
                'importe_cobrado', 'importe_libre', 'fcobro', 'fcreacion', 'fmodificacion',
            ])
                ->where('idcliente', $id)
                ->whereNull('fbaja');

            if ($request->filled('from')) {
                $query->where('fcobro', '>=', $request->get('from'));
            }

            if ($request->filled('to')) {
                $query->where('fcobro', '<=', $request->get('to'));
            }

            $payments = $query->orderByDesc('fcobro')
                ->offset($offset)
                ->limit($limit + 1)
                ->get();

            $hasMore = $payments->count() > $limit;
            if ($hasMore) {
                $payments = $payments->slice(0, $limit);
            }

            $data = $payments->map(fn ($p) => [
                'id' => $p->idcobrocli_central,
                'payment_id' => $p->idcobrocli,
                'method' => $p->idformapago,
                'voucher' => $p->idvale,
                'cash_register' => $p->idcaja,
                'transporter' => $p->idtransportista,
                'amount_collected' => (float) $p->importe_cobrado,
                'amount_free' => $p->importe_libre !== null ? (float) $p->importe_libre : null,
                'date' => $p->fcobro ? Carbon::parse($p->fcobro)->format('Y-m-d H:i:s') : null,
                'status' => $p->estado,
                'created' => $p->fcreacion?->format('Y-m-d H:i:s'),
                'updated' => $p->fmodificacion?->format('Y-m-d H:i:s'),
            ])->values();

            $totalTime = microtime(true) - $startTime;
            Log::debug('=== TIEMPO Customer Payments: '.round($totalTime * 1000, 2).'ms ===');

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data->all()),
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => $payments->count(),
                    'hasMore' => $hasMore,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@payments', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => $this->sanitizeOracleError($e->getMessage())], 200);
        }
    }

    /**
     * Deudas vivas del cliente (JOIN con albaranes).
     *
     * GET /api/erp/customer/{id}/debts
     */
    public function debts(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:debts:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos', 'riesgo', 'riesgomaximo'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $debts = DeudacliCentral::query()
                ->from('deudacli_central as d')
                ->join('albarancli_central as a', 'd.idalbarancli_central', '=', 'a.idalbarancli_central')
                ->where('a.idcliente', $id)
                ->whereNull('d.fbaja')
                ->select([
                    'd.iddeudacli_central as id',
                    'd.iddeudacli as debt_id',
                    'd.idcobrocli_central as payment_id',
                    'd.idalbarancli_central as delivery_id',
                    'a.nalbarancli as delivery_number',
                    'a.falbaran as delivery_date',
                    'd.idformapago as payment_method',
                    'd.importe as amount',
                    'd.estado as status',
                    'd.fcreacion as created',
                ])
                ->orderByDesc('d.fcreacion')
                ->get()
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'debt_id' => $d->debt_id,
                    'payment_id' => $d->payment_id,
                    'delivery_id' => $d->delivery_id,
                    'delivery_number' => $d->delivery_number,
                    'delivery_date' => $d->delivery_date ? Carbon::parse($d->delivery_date)->format('Y-m-d') : null,
                    'payment_method' => $d->payment_method,
                    'amount' => (float) $d->amount,
                    'status' => (bool) $d->status,
                    'created' => $d->created ? Carbon::parse($d->created)->format('Y-m-d H:i:s') : null,
                ])->values();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'risk' => [
                    'current' => $customer->riesgo !== null ? (float) $customer->riesgo : null,
                    'max_allowed' => $customer->riesgomaximo !== null ? (float) $customer->riesgomaximo : null,
                ],
                'debts' => $debts,
                'statistics' => [
                    'debts' => [
                        'total' => $debts->count(),
                        'amount_total' => round((float) $debts->sum('amount'), 2),
                    ],
                ],
            ];
        }, 'Debts');
    }

    /**
     * Saldo agregado del cliente (facturado, cobrado, deuda).
     *
     * GET /api/erp/customer/{id}/balance
     */
    public function balance(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:balance:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos', 'riesgo', 'riesgomaximo'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $invoiced = (float) FacturacliCentral::where('idcliente', $id)
                ->whereNull('fbaja')
                ->join('lfacturacli_central as l', 'facturacli_central.idfacturacli', '=', 'l.idfacturacli')
                ->sum('l.total_con_impuestos');

            $collected = (float) CobrocliCentral::where('idcliente', $id)
                ->whereNull('fbaja')
                ->sum('importe_cobrado');

            $debt = (float) DeudacliCentral::query()
                ->from('deudacli_central as d')
                ->join('albarancli_central as a', 'd.idalbarancli_central', '=', 'a.idalbarancli_central')
                ->where('a.idcliente', $id)
                ->whereNull('d.fbaja')
                ->sum('d.importe');

            $loyaltyPoints = (int) Puntofidelizacion::where('idcliente', $id)
                ->where('estado', 1)
                ->sum('puntos');

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'balance' => [
                    'invoiced' => round($invoiced, 2),
                    'collected' => round($collected, 2),
                    'pending' => round($debt, 2),
                ],
                'risk' => [
                    'current' => $customer->riesgo !== null ? (float) $customer->riesgo : null,
                    'max_allowed' => $customer->riesgomaximo !== null ? (float) $customer->riesgomaximo : null,
                ],
                'loyalty_points' => $loyaltyPoints,
            ];
        }, 'Balance');
    }

    /**
     * Vales del cliente.
     *
     * GET /api/erp/customer/{id}/vouchers
     */
    public function vouchers(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:vouchers:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $vouchers = Vale::select([
                'idvale_anterior', 'idvale', 'idcliente', 'idalmacen',
                'importe', 'tipo', 'estado', 'fvalidez', 'fanulacion',
                'observaciones', 'tiene_codigo_comprobacion', 'idvale_original',
            ])
                ->where('idcliente', $id)
                ->orderByDesc('idvale')
                ->get()
                ->map(fn ($v) => [
                    'id' => $v->idvale_anterior,
                    'voucher_id' => $v->idvale,
                    'original_voucher_id' => $v->idvale_original,
                    'warehouse' => $v->idalmacen,
                    'amount' => $v->importe !== null ? (float) $v->importe : null,
                    'type' => $v->tipo,
                    'valid_until' => $v->fvalidez ? Carbon::parse($v->fvalidez)->format('Y-m-d') : null,
                    'cancelled_at' => $v->fanulacion ? Carbon::parse($v->fanulacion)->format('Y-m-d H:i:s') : null,
                    'observations' => $v->observaciones,
                    'has_check_code' => (bool) $v->tiene_codigo_comprobacion,
                    'available' => $v->estado,
                ])->values();

            $now = now();
            $active = $vouchers->filter(fn ($v) => $v['available']
                && ! $v['cancelled_at']
                && (! $v['valid_until'] || Carbon::parse($v['valid_until'])->gte($now)))->count();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'vouchers' => $vouchers,
                'statistics' => [
                    'vouchers' => [
                        'total' => $vouchers->count(),
                        'active' => $active,
                        'amount_total' => round((float) $vouchers->sum('amount'), 2),
                    ],
                ],
            ];
        }, 'Vouchers');
    }

    /**
     * Bonos del cliente.
     *
     * GET /api/erp/customer/{id}/bonuses
     */
    public function bonuses(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:bonuses:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $bonuses = BonoPromocion::select([
                'idbono_promocion', 'idcliente', 'idalbarancli', 'idlpromocion',
                'idtbono_promocion', 'idcatalogo_consumo', 'idliquidacionbono',
                'fvalidez_desde', 'fvalidez_hasta', 'estado', 'estado_envio',
                'tipo', 'tipotarjeta', 'importe', 'importeminimoventa',
                'enviado_sms', 'enviado_mail', 'enviado_carta', 'bonoimpreso',
                'fecha', 'fconsumo', 'fenvio',
            ])
                ->where('idcliente', $id)
                ->orderByDesc('fecha')
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->idbono_promocion,
                    'delivery_id' => $b->idalbarancli,
                    'promotion_line' => $b->idlpromocion,
                    'bonus_type' => $b->idtbono_promocion,
                    'consumption_catalog' => $b->idcatalogo_consumo,
                    'liquidation' => $b->idliquidacionbono,
                    'amount' => $b->importe !== null ? (float) $b->importe : null,
                    'minimum_purchase' => $b->importeminimoventa !== null ? (float) $b->importeminimoventa : null,
                    'type' => $b->tipo,
                    'card_type' => $b->tipotarjeta,
                    'valid_from' => $b->fvalidez_desde ? Carbon::parse($b->fvalidez_desde)->format('Y-m-d') : null,
                    'valid_until' => $b->fvalidez_hasta ? Carbon::parse($b->fvalidez_hasta)->format('Y-m-d') : null,
                    'date' => $b->fecha ? Carbon::parse($b->fecha)->format('Y-m-d H:i:s') : null,
                    'consumed_at' => $b->fconsumo ? Carbon::parse($b->fconsumo)->format('Y-m-d H:i:s') : null,
                    'sent_at' => $b->fenvio ? Carbon::parse($b->fenvio)->format('Y-m-d H:i:s') : null,
                    'available' => $b->estado,
                    'send_status' => $b->estado_envio,
                    'sent' => [
                        'sms' => (bool) $b->enviado_sms,
                        'email' => (bool) $b->enviado_mail,
                        'mail' => (bool) $b->enviado_carta,
                        'printed' => (bool) $b->bonoimpreso,
                    ],
                ])->values();

            $now = now();
            $active = $bonuses->filter(fn ($b) => $b['available']
                && ! $b['consumed_at']
                && (! $b['valid_until'] || Carbon::parse($b['valid_until'])->gte($now)))->count();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'bonuses' => $bonuses,
                'statistics' => [
                    'bonuses' => [
                        'total' => $bonuses->count(),
                        'active' => $active,
                        'consumed' => $bonuses->whereNotNull('consumed_at')->count(),
                        'amount_total' => round((float) $bonuses->sum('amount'), 2),
                    ],
                ],
            ];
        }, 'Bonuses');
    }

    /**
     * Puntos de fidelización del cliente.
     *
     * GET /api/erp/customer/{id}/loyalty-points
     */
    public function loyaltyPoints(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:loyalty:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos', 'idtarjeta'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $movements = Puntofidelizacion::select([
                'idpuntofidelizacion', 'idcliente', 'idtarjeta', 'idalmacen',
                'idliquidacion', 'idalbarancli', 'puntos', 'fecha', 'estado',
            ])
                ->where('idcliente', $id)
                ->orderByDesc('fecha')
                ->limit(100)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->idpuntofidelizacion,
                    'card' => $p->idtarjeta,
                    'warehouse' => $p->idalmacen,
                    'liquidation' => $p->idliquidacion,
                    'delivery_id' => $p->idalbarancli,
                    'points' => (int) $p->puntos,
                    'date' => $p->fecha ? Carbon::parse($p->fecha)->format('Y-m-d H:i:s') : null,
                    'available' => $p->estado,
                ])->values();

            $balance = (int) Puntofidelizacion::where('idcliente', $id)
                ->where('estado', 1)
                ->sum('puntos');

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'main_card' => $customer->idtarjeta,
                'balance' => $balance,
                'movements' => $movements,
                'statistics' => [
                    'movements' => ['total' => $movements->count()],
                ],
            ];
        }, 'LoyaltyPoints');
    }

    /**
     * Limpiar cache de un cliente concreto.
     *
     * DELETE /api/erp/customer/{id}/cache
     */
    public function clearCache(int $id): JsonResponse
    {
        $this->forgetCache($id);

        return response()->json([
            'success' => true,
            'message' => "Customer {$id} cache cleared",
        ]);
    }

    private function forgetCache(int $id): void
    {
        $keys = [
            'summary', 'addresses', 'contact', 'personal', 'lopd',
            'cards', 'accounts', 'catalogs', 'quotas',
            'debts', 'balance', 'vouchers', 'bonuses', 'loyalty',
        ];

        foreach ($keys as $section) {
            cache()->forget("customer:{$section}:{$id}");
        }

        // Las keys con sub-id (customer:order:{id}:*, customer:invoice:{id}:*, customer:delivery:{id}:*)
        // se invalidan solo por TTL. Acceptable: el detalle es de baja escritura.
    }

    /**
     * Helper para envolver el patrón estándar:
     * - cachedResult con cache toggle
     * - log de tiempo y cache hit/miss
     * - response JSON estándar con meta
     * - manejo 404/500
     */
    private function cachedSimple(string $cacheKey, int $id, \Closure $callback, string $logLabel): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult(
                $cacheKey,
                fn () => $callback($id)
            );

            $totalTime = microtime(true) - $startTime;
            Log::debug("=== TIEMPO Customer {$logLabel}: ".round($totalTime * 1000, 2).'ms (Cache: '.($fromCache ? 'HIT' : 'MISS').') ===');

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
            Log::error("Error CustomerController@{$logLabel}", ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json([
                'success' => false,
                'error' => $this->sanitizeOracleError($e->getMessage()),
            ], 200);
        }
    }

    private function sanitizeOracleError(string $message): string
    {
        if (str_contains($message, '942') || str_contains($message, 'ORA-00942')) {
            return 'Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.';
        }

        if (str_contains($message, 'ORA-')) {
            preg_match('/ORA-\d+:\s*[^\n]+/', $message, $matches);

            return $matches[0] ?? 'Oracle database error';
        }

        return $message;
    }

    private function maskIban(?string $iban): ?string
    {
        if (! $iban) {
            return null;
        }
        $iban = preg_replace('/\s+/', '', $iban);
        if (strlen($iban) <= 8) {
            return $iban;
        }

        return substr($iban, 0, 4).str_repeat('*', strlen($iban) - 8).substr($iban, -4);
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

    private function mapOrders(array $rows): array
    {
        return array_map(fn ($p) => [
            'id' => $p['idpedidocli_central'],
            'order_id' => $p['idpedidocli'],
            'number' => $p['npedidocli'],
            'status' => $p['estado'],
            'warehouse' => $p['idalmacen'],
            'origin' => $p['idorigenpedidocli'],
            'type' => $p['tipopedido'],
            'date' => $p['fpedido'],
            'expected_date' => $p['fprevista'],
            'served_date' => $p['fservido'],
            'observations' => $p['observaciones'],
            'created' => $p['fcreacion'],
            'updated' => $p['fmodificacion'],
        ], $rows);
    }
}
