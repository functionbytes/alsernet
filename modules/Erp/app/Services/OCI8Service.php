<?php

namespace Modules\Erp\Services;

/**
 * Servicio para conexiones OCI8 directo (sin PDO)
 * Evita timeouts de Eloquent/Query Builder
 */
class OCI8Service
{
    private $connection;

    private $host;

    private $port;

    private $database;

    private $username;

    private $password;

    private static $statementCache = [];

    /**
     * Segundos sin uso a partir de los cuales se hace ping antes de reutilizar
     * la conexión (relevante en workers de cola, no en peticiones HTTP).
     */
    private const PING_AFTER_IDLE_SECONDS = 30;

    private int $lastUsedAt = 0;

    private static ?bool $callTimeoutSupported = null;

    private static function callTimeoutSupported(): bool
    {
        return self::$callTimeoutSupported ??= function_exists('oci_set_call_timeout')
            && function_exists('oci_client_version')
            && version_compare((string) oci_client_version(), '18', '>=');
    }

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Misma fuente de credenciales que DB::connection('oracle'): la config
     * base pasada por el hook 'dynamic' de yajra (Settings del panel). Antes
     * se leía config() sin procesar y, con credenciales definidas solo en el
     * panel, OCI8 conectaba con usuario vacío (ORA-24415).
     */
    private function loadConfig(): void
    {
        $config = (array) config('database.connections.oracle', []);

        if (! empty($config['dynamic']) && is_callable($config['dynamic'])) {
            call_user_func_array($config['dynamic'], [&$config]);
        }

        $this->host = $config['host'] ?? null;
        $this->port = $config['port'] ?? null;
        // El TNS connect string usa SERVICE_NAME (no SID). Fallback al campo
        // 'database' por compatibilidad con instalaciones donde sólo se definió ese.
        $this->database = ($config['service_name'] ?? null) ?: ($config['database'] ?? null);
        $this->username = $config['username'] ?? null;
        $this->password = $config['password'] ?? null;
    }

    public function connect()
    {
        // query() llama a connect() en cada consulta: antes eso hacía un
        // SELECT 1 FROM DUAL previo SIEMPRE (una ida y vuelta extra por
        // consulta). Ahora solo se comprueba si la conexión lleva un rato
        // parada, que es cuando Oracle/firewall pueden haberla cortado.
        if ($this->connection) {
            if (time() - $this->lastUsedAt < self::PING_AFTER_IDLE_SECONDS || $this->isAlive()) {
                $this->lastUsedAt = time();

                return $this->connection;
            }
        }

        $this->connection = null;
        // Recargar por si los ajustes cambiaron desde que se construyó (workers).
        $this->loadConfig();

        if (empty($this->host) || empty($this->database)) {
            throw new \Exception(sprintf(
                'OCI8 Connection failed: configuración Oracle incompleta (host=%s, service=%s). '.
                'Verifica ORACLE_HOST y ORACLE_SERVICE_NAME en .env.',
                $this->host ?: '(vacío)',
                $this->database ?: '(vacío)'
            ));
        }

        // Easy Connect string — requiere SERVICE_NAME (no SID) tras la barra.
        $tns = "{$this->host}:{$this->port}/{$this->database}";

        // oci_connect (no persistente) — evita que conexiones muertas (ORA-03113) contaminen el worker PHP-FPM
        $this->connection = oci_connect($this->username, $this->password, $tns, 'AL32UTF8');

        if (! $this->connection) {
            $error = oci_error();
            throw new \Exception('OCI8 Connection failed: '.$error['message']);
        }

        $this->lastUsedAt = time();

        return $this->connection;
    }

    private function isAlive(): bool
    {
        if (! $this->connection) {
            return false;
        }
        $stm = @oci_parse($this->connection, 'SELECT 1 FROM DUAL');
        if (! $stm) {
            return false;
        }
        $ok = @oci_execute($stm, OCI_NO_AUTO_COMMIT);
        @oci_free_statement($stm);

        return (bool) $ok;
    }

    public function query(string $sql, array $params = [], ?int $timeoutMs = null): array
    {
        $conn = $this->connect();
        $stm = oci_parse($conn, $sql);

        if (! $stm) {
            $error = oci_error($conn);
            throw new \Exception('OCI8 Parse failed: '.$error['message']);
        }

        // Set prefetch to reduce round trips
        oci_set_prefetch($stm, 100);

        // Timeout por llamada: requiere Oracle Client 18c+ y se aplica a la
        // CONEXIÓN (antes se pasaba el statement). Con un cliente antiguo
        // emitía un NOTICE en cada consulta; ahora ni se intenta.
        if ($timeoutMs !== null && self::callTimeoutSupported()) {
            try {
                oci_set_call_timeout($conn, $timeoutMs);
            } catch (\Throwable) {
                self::$callTimeoutSupported = false;
            }
        }

        // Bind parameters BY REFERENCE — oci_bind_by_name retains a reference
        // to the variable until oci_execute(). Iterating with $params[$key]
        // re-binds the same variable each loop and ends up pointing all binds
        // to the last value. We keep a stable bound-vars array alive for the
        // duration of the execute.
        $bound = [];
        foreach ($params as $key => $value) {
            $bindKey = is_numeric($key) ? (int) $key + 1 : ':'.$key;
            $bound[$bindKey] = $value;
            oci_bind_by_name($stm, (string) $bindKey, $bound[$bindKey]);
        }

        // Execute with OCI_COMMIT_ON_SUCCESS
        if (! oci_execute($stm, OCI_COMMIT_ON_SUCCESS)) {
            $error = oci_error($stm);
            throw new \Exception('OCI8 Execute failed: '.$error['message']);
        }

        // Use oci_fetch_all for maximum performance
        $result = [];
        oci_fetch_all($stm, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);

        // Fast lowercase conversion using array_change_key_case
        if (! empty($rows)) {
            foreach ($rows as &$row) {
                $row = array_change_key_case($row, CASE_LOWER);
            }
            $result = $rows;
        }

        oci_free_statement($stm);

        return $result;
    }

    public function queryOne(string $sql, array $params = [], ?int $timeoutMs = null): ?array
    {
        $results = $this->query($sql, $params, $timeoutMs);

        return $results[0] ?? null;
    }

    public function count(string $table, array $where = []): int
    {
        $table = $this->sanitizeIdentifier($table);
        $sql = "SELECT COUNT(*) as cnt FROM {$table}";

        if (! empty($where)) {
            $this->assertSafeBindKeys(array_keys($where));
            $conditions = array_map(fn ($k) => $this->sanitizeIdentifier($k)." = :$k", array_keys($where));
            $sql .= ' WHERE '.implode(' AND ', $conditions);
        }

        $result = $this->queryOne($sql, $where);

        return (int) ($result['CNT'] ?? 0);
    }

    public function select(string $table, int $limit = 10, int $offset = 0, array $where = [], array $columns = ['*']): array
    {
        $table = $this->sanitizeIdentifier($table);
        $cols = implode(', ', array_map(fn ($c) => $c === '*' ? '*' : $this->sanitizeIdentifier($c), $columns));
        $sql = "SELECT {$cols} FROM {$table}";

        if (! empty($where)) {
            $this->assertSafeBindKeys(array_keys($where));
            $conditions = array_map(fn ($k) => $this->sanitizeIdentifier($k)." = :$k", array_keys($where));
            $sql .= ' WHERE '.implode(' AND ', $conditions);
        }

        // Defensive casts: callers pass paginator input that may be a string
        $offset = max(0, (int) $offset);
        $limit = max(1, (int) $limit);

        $sql .= " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";

        return $this->query($sql, $where);
    }

    /**
     * Whitelist Oracle identifiers to alphanumerics + underscore + dot (for schema.table).
     * Anything else is rejected — prevents SQL injection through $table/$column inputs.
     */
    private function sanitizeIdentifier(string $identifier): string
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $identifier)) {
            throw new \InvalidArgumentException("Invalid Oracle identifier: {$identifier}");
        }

        return $identifier;
    }

    /**
     * Bind keys are interpolated into the SQL string as `:key`, so they must be
     * safe identifiers (no spaces, no symbols).
     */
    private function assertSafeBindKeys(array $keys): void
    {
        foreach ($keys as $k) {
            if (! is_string($k) || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $k)) {
                throw new \InvalidArgumentException("Invalid bind key: {$k}");
            }
        }
    }

    public function close()
    {
        if ($this->connection) {
            oci_close($this->connection);
            $this->connection = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
