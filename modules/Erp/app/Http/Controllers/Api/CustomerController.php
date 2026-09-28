<?php

namespace Modules\Erp\Http\Controllers\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Http\Requests\Customer\ListCustomersRequest;
use Modules\Erp\Http\Requests\Customer\UpdateLopdRequest;
use Modules\Erp\Http\Requests\Customer\UpsertCustomerRequest;
use Modules\Erp\Models\Oracle\Cliente\ClientecatalogoCent;
use Modules\Erp\Models\Oracle\Cliente\ClienteCent;
use Modules\Erp\Services\OCI8Service;
use Modules\Erp\Support\ErpErrorSanitizer;

/**
 * CUSTOMER API (Helpdesk / CallCenter) — colección, alta/LOPD, búsqueda y resumen.
 *
 * El resto de la ficha está repartido por dominio:
 *   CustomerProfileController     datos personales, LOPD, direcciones, tarjetas, cuentas...
 *   CustomerCommercialController  pedidos, albaranes y facturas
 *   CustomerFinancialController   cobros, deudas y saldo
 *   CustomerPromotionalController vales, bonos y puntos
 *
 * Base URL: /api/erp/customer
 */
class CustomerController extends AbstractCustomerController
{
    /**
     * Minutos de caché de la audiencia de cumpleaños y su desglose.
     */
    private const BIRTHDAY_CACHE_MINUTES = 10;

    /**
     * Lista paginada de clientes con filtros completos.
     *
     * GET /api/erp/customer?{filters}
     *
     * Filters: id, cif, email, surnames, phone, birth_date,
     * lopd_from, lopd_to, deleted_from, deleted_to
     *
     * Filtros de segmentación para envíos (usados por HelpdeskBirthday):
     *   birthday=MM-DD[,MM-DD]  cumpleaños por día y mes, sin importar el año
     *   commercial_optin=1      excluye NO_INFORMACION_COMERCIAL_LOPD
     *   lopd_accepted=1         solo con FACEPTACION_LOPD informada
     *   has_email=1             solo con email no vacío
     *
     * Los dados de baja (FBAJA) quedan siempre fuera.
     *
     * Pagination: limit (max 100), offset
     */
    public function list(ListCustomersRequest $request): JsonResponse
    {
        try {
            $limit = max(1, min((int) $request->get('limit', 10), 100));
            $offset = max(0, (int) $request->get('offset', 0));

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
                // Igual que /customer/search: UPPER(EMAIL) sin distinguir
                // mayúsculas, para que un único índice UPPER(EMAIL) sirva a
                // ambos (ver database/oracle/recommended_indexes.sql).
                $conditions[] = 'UPPER(t.EMAIL) = UPPER(?)';
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
            // Cumpleaños: día y mes, sin importar el año. Acepta una o varias
            // fechas 'MM-DD' separadas por coma (el 29-feb se consulta junto al
            // 28-feb en años no bisiestos).
            if ($request->filled('birthday')) {
                $days = array_values(array_filter(
                    array_map('trim', explode(',', (string) $request->get('birthday'))),
                    static fn (string $d): bool => preg_match('/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $d) === 1
                ));

                if ($days === []) {
                    return response()->json([
                        'success' => false,
                        'error' => "El filtro 'birthday' espera una o más fechas con formato MM-DD.",
                    ], 422);
                }

                $placeholders = implode(', ', array_fill(0, count($days), '?'));
                $conditions[] = "TO_CHAR(t.FNACIMIENTO, 'MM-DD') IN ({$placeholders})";
                $bindings = array_merge($bindings, $days);
            }
            // Solo quienes no han marcado "no quiero información comercial".
            if ($request->boolean('commercial_optin')) {
                $conditions[] = '(t.NO_INFORMACION_COMERCIAL_LOPD IS NULL OR t.NO_INFORMACION_COMERCIAL_LOPD = 0)';
            }
            if ($request->boolean('lopd_accepted')) {
                $conditions[] = 't.FACEPTACION_LOPD IS NOT NULL';
            }
            if ($request->boolean('has_email')) {
                // Nada de TRIM(t.EMAIL) <> '': en Oracle la cadena vacía ES
                // NULL, así que esa comparación es siempre UNKNOWN y el filtro
                // se llevaba por delante a TODOS los clientes (0 resultados con
                // 666 que sí tienen correo). Basta con NOT NULL, y el INSTR
                // descarta además lo que no es una dirección.
                $conditions[] = "t.EMAIL IS NOT NULL AND INSTR(t.EMAIL, '@') > 0";
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
                    "TO_CHAR(t.FNACIMIENTO, 'YYYY-MM-DD') AS FNACIMIENTO, ".
                    "TO_CHAR(t.FCREACION, 'YYYY-MM-DD HH24:MI:SS') AS FCREACION, ".
                    "TO_CHAR(t.FMODIFICACION, 'YYYY-MM-DD HH24:MI:SS') AS FMODIFICACION, ".
                    "TO_CHAR(t.FBAJA, 'YYYY-MM-DD') AS FBAJA";

            $rownum = $limit + 1;

            // Audiencia de cumpleaños (HelpdeskBirthday la recorre página a
            // página): cada página era un recorrido completo de CLIENTE_CENT
            // (~1 s) y, sin ORDER BY, las páginas podían repetir o saltarse
            // clientes. Se consulta UNA vez la audiencia entera, ordenada, y se
            // cachea 10 min; las páginas salen de ahí.
            if ($request->filled('birthday')) {
                $audience = cache()->remember(
                    'erp:customer:birthday-audience:'.md5($where.'|'.json_encode($bindings)),
                    now()->addMinutes(self::BIRTHDAY_CACHE_MINUTES),
                    function () use ($cols, $where, $bindings) {
                        // Orden en PHP (son cientos de filas): con ORDER BY
                        // IDCLIENTE Oracle recorría la tabla por el índice de la
                        // PK, fila a fila (13,9 s frente a ~1,5 s del recorrido).
                        $rows = DB::connection('oracle')->select("SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT t {$where}", $bindings);
                        usort($rows, fn ($a, $b) => (int) $a->idcliente <=> (int) $b->idcliente);

                        return $rows;
                    }
                );
            } elseif ($offset === 0) {
                $sql = "SELECT /*+ FIRST_ROWS({$limit}) */ {$cols} FROM DEVELOPER.CLIENTE_CENT t {$where} AND ROWNUM <= {$rownum}";
            } else {
                $totalLimit = $offset + $limit + 1;
                $sql = 'SELECT * FROM (SELECT t2.*, ROWNUM AS rn FROM ('.
                       "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT t {$where}".
                       ") t2 WHERE ROWNUM <= {$totalLimit}) WHERE rn > {$offset}";
            }

            $rows = isset($audience)
                ? array_slice($audience, $offset, $limit + 1)
                : DB::connection('oracle')->select($sql, $bindings);
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
                'birth_date' => $c->fnacimiento,
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

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
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
    public function create(UpsertCustomerRequest $request): JsonResponse
    {
        // La validación ya ocurrió en el FormRequest: no se abre la
        // transacción Oracle para peticiones que se van a rechazar.
        DB::connection('oracle')->beginTransaction();

        try {
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

            return response()->json([
                'success' => true,
                'data' => ['id' => $customer->idcliente],
            ], 201, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            DB::connection('oracle')->rollBack();
            Log::error('Error CustomerController@create', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }

    /**
     * Actualizar consentimiento LOPD por email.
     *
     * PATCH /api/erp/customer/lopd
     *
     * Body: email, accepted_at, no_commercial_info, no_data_to_third_parties
     */
    public function updateLopd(UpdateLopdRequest $request): JsonResponse
    {
        try {
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

            return response()->json([
                'success' => true,
                'data' => ['id' => $customer->idcliente],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@updateLopd', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }

    /**
     * Búsqueda unificada de clientes (DNI, email, teléfono, tarjeta, código internet, apellidos).
     *
     * GET /api/erp/customer/search?q=...&limit=10
     */
    /**
     * Desglose de la audiencia de cumpleaños de un día.
     *
     * GET /api/erp/customer/birthday-stats?day=MM-DD[,MM-DD]
     *
     * Devuelve, en UNA sola consulta, cuántos cumplen años y cuántos quedan
     * fuera por cada motivo. Existe porque el listado filtra en el WHERE: los
     * descartados no llegan a viajar, así que sin esto una campaña solo puede
     * decir "hoy escribo a 576" sin poder explicar qué pasó con los otros 151.
     *
     * Los motivos NO son excluyentes entre sí (alguien puede estar de baja y
     * además no tener correo); cada uno cuenta su condición por separado y
     * `writable` es quien pasa todas a la vez.
     */
    public function birthdayStats(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        $days = array_values(array_filter(
            array_map('trim', explode(',', (string) $request->get('day', now()->format('m-d')))),
            static fn (string $d): bool => preg_match('/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $d) === 1
        ));

        if ($days === []) {
            return response()->json([
                'success' => false,
                'error' => "El parámetro 'day' espera una o más fechas con formato MM-DD.",
            ], 422);
        }

        $placeholders = implode(', ', array_fill(0, count($days), '?'));

        // Cuatro COUNT condicionales sobre el mismo recorrido: pedirlos por
        // separado serían cinco consultas de ~3 s cada una.
        $sql = "SELECT
                    COUNT(*) AS TOTAL,
                    SUM(CASE WHEN t.FBAJA IS NOT NULL THEN 1 ELSE 0 END) AS UNSUBSCRIBED,
                    SUM(CASE WHEN t.EMAIL IS NULL OR INSTR(t.EMAIL, '@') = 0 THEN 1 ELSE 0 END) AS NO_EMAIL,
                    SUM(CASE WHEN t.FACEPTACION_LOPD IS NULL THEN 1 ELSE 0 END) AS NO_LOPD,
                    SUM(CASE WHEN NVL(t.NO_INFORMACION_COMERCIAL_LOPD, 0) = 1 THEN 1 ELSE 0 END) AS NO_COMMERCIAL,
                    SUM(CASE WHEN t.FBAJA IS NULL
                              AND t.EMAIL IS NOT NULL AND INSTR(t.EMAIL, '@') > 0
                              AND t.FACEPTACION_LOPD IS NOT NULL
                              AND NVL(t.NO_INFORMACION_COMERCIAL_LOPD, 0) = 0
                        THEN 1 ELSE 0 END) AS WRITABLE
                FROM DEVELOPER.CLIENTE_CENT t
                WHERE t.FNACIMIENTO IS NOT NULL
                  AND TO_CHAR(t.FNACIMIENTO, 'MM-DD') IN ({$placeholders})";

        try {
            // DB::connection('oracle'): OCI8Service ya comparte credenciales
            // (ambos pasan por ErpServiceProvider::applyDynamicOracleConfig()),
            // pero aquí se necesita el reconector de Laravel.
            //
            // Con reintento y reconexión: la conexión con Oracle se cae a ratos
            // ("Lost connection and no reconnector available") y a la siguiente
            // responde bien. Sin esto, un corte puntual deja la campaña sin
            // poder explicar su audiencia.
            // Cacheado por día(s) 10 min: es un recorrido completo de
            // CLIENTE_CENT (~2 s) y el panel lo pide al abrir cada campaña.
            // TTL corto para que una baja de LOPD se refleje pronto.
            sort($days);
            $result = cache()->remember(
                'erp:customer:birthday-stats:'.implode(',', $days),
                now()->addMinutes(self::BIRTHDAY_CACHE_MINUTES),
                fn () => $this->selectFromOracleWithRetry($sql, $days)
            );
            $row = (array) ($result[0] ?? []);

            $n = static fn (string $key): int => (int) ($row[$key] ?? $row[strtolower($key)] ?? 0);

            return response()->json([
                'success' => true,
                'days' => $days,
                'stats' => [
                    'total' => $n('TOTAL'),
                    'unsubscribed' => $n('UNSUBSCRIBED'),
                    'no_email' => $n('NO_EMAIL'),
                    'no_lopd' => $n('NO_LOPD'),
                    'no_commercial_optin' => $n('NO_COMMERCIAL'),
                    'writable' => $n('WRITABLE'),
                ],
                'took_ms' => (int) round((microtime(true) - $startTime) * 1000),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Log::error('[ERP] Fallo al contar la audiencia de cumpleaños', [
                'days' => $days,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'No se pudo consultar la audiencia de cumpleaños.',
            ], 502, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Consulta Oracle reintentando cuando la conexión se ha caído.
     *
     * @param  array<int, mixed>  $bindings
     * @return array<int, object>
     */
    private function selectFromOracleWithRetry(string $sql, array $bindings, int $attempts = 3): array
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::connection('oracle')->select($sql, $bindings);
            } catch (\Throwable $e) {
                if ($attempt >= $attempts) {
                    throw $e;
                }

                // Reconectar explícitamente: el driver no lo hace solo y sin
                // esto los reintentos fallarían todos por la misma conexión rota.
                DB::connection('oracle')->reconnect();
                usleep(300_000);
            }
        }
    }

    /**
     * Palabras de una búsqueda por texto, en mayúsculas (máx. 4, de 2+
     * caracteres; las más cortas — "de", "y" — solo ensancharían el LIKE).
     *
     * @return list<string>
     */
    private function searchWords(string $q): array
    {
        $words = preg_split('/\s+/u', mb_strtoupper(trim($q))) ?: [];

        return array_slice(array_values(array_filter($words, fn ($w) => mb_strlen($w) >= 2)), 0, 4);
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $q = trim((string) $request->get('q', ''));
            $limit = max(1, min((int) $request->get('limit', 10), 50));
            // Paginación ("Cargar más"): se piden offset+limit+1 filas con el
            // mismo ROWNUM de siempre y se descartan las ya mostradas.
            $offset = max(0, (int) $request->get('offset', 0));

            if ($q === '') {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'pagination' => ['limit' => $limit, 'offset' => 0, 'count' => 0, 'hasMore' => false],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            $oci8 = app(OCI8Service::class);
            $n = $offset + $limit + 1;
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
            } elseif (count($words = $this->searchWords($q)) > 1) {
                // Varias palabras ("monica regueira", "regueira timiraos"):
                // nombre y apellidos van en columnas distintas, así que ni
                // APELLIDOS LIKE 'MONICA REGUEIRA%' ni NOMBRE LIKE ... podían
                // coincidir — eran dos recorridos completos inútiles (~14 s
                // cada uno) que además pasaban del timeout del cliente. Ahora
                // es UN recorrido: cada palabra debe aparecer en nombre+apellidos.
                $where = [];
                $binds = [];
                foreach ($words as $i => $word) {
                    $where[] = "UPPER(NOMBRE || ' ' || APELLIDOS) LIKE :w{$i}";
                    $binds["w{$i}"] = '%'.$word.'%';
                }

                $rows = $oci8->query(
                    "SELECT {$cols} FROM DEVELOPER.CLIENTE_CENT WHERE FBAJA IS NULL AND ".implode(' AND ', $where)." AND ROWNUM <= {$n}",
                    $binds,
                    30000
                );
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

            $rows = array_slice($rows, $offset);
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
            Log::error('Error CustomerController@search', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => ErpErrorSanitizer::forClient($e),
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

            if (empty($rows)) {
                return response()->json([
                    'success' => true,
                    'exists' => false,
                    'data' => null,
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            $c = $rows[0];

            return response()->json([
                'success' => true,
                'exists' => true,
                'matched_by' => $matchedBy,
                'data' => $this->cleanUtf8Array([
                    'id' => $c['idcliente'],
                    'label' => $c['nombre'],
                    'surnames' => $c['apellidos'],
                    'cif' => $c['cif'],
                    'email' => $c['email'],
                    'code_internet' => $c['codigo_internet'],
                    'card' => $c['idtarjeta'],
                    'available' => (bool) ($c['estado'] ?? false),
                    'deleted_at' => $c['fbaja'],
                    'created' => $c['fcreacion'],
                    'updated' => $c['fmodificacion'],
                ]),
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@findByIdWeb', ['idweb' => $idweb, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => ErpErrorSanitizer::forClient($e),
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
                'error' => ErpErrorSanitizer::forClient($e),
            ], 500);
        }
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
}
