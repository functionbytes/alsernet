<?php

namespace Modules\Erp\Traits;

use Modules\Core\Models\Setting;
use Modules\Erp\Services\OCI8Service;

/**
 * Trait para mejorar performance de modelos Oracle usando OCI8 directo
 *
 * Usage:
 * - Model::fastPaginate() - Query rápida sin relaciones
 * - Model::query()->with() - Query normal con relaciones Eloquent
 */
trait UsesOCI8Performance
{
    /**
     * Paginación ultra-rápida sin relaciones (usa OCI8 directo)
     */
    public static function fastPaginate(array $filters = [], int $limit = 10, int $offset = 0, ?bool $useCache = null, int|string|null $afterId = null): array
    {
        $instance = new static;

        // Read cache setting from database if not explicitly set
        if ($useCache === null) {
            $settings = Setting::getErpSettings();
            $useCache = $settings['oracle_enable_cache'] ?? true;
        }

        // Cache key for Redis
        if ($useCache) {
            $cacheKey = 'erp:'.$instance->getTable().':'.md5(json_encode($filters).$limit.$offset.'|'.$afterId);

            $cached = cache()->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $oci8 = app(OCI8Service::class);
        $table = $instance->getTable();
        $conditions = [];
        $params = [];

        // Build filters dinámicamente
        foreach ($filters as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            if (str_contains($value, '%')) {
                $conditions[] = "UPPER(t.$key) LIKE UPPER(:$key)";
                $params[$key] = $value;
            } else {
                $conditions[] = "t.$key = :$key";
                $params[$key] = $value;
            }
        }

        // Build WHERE clause
        $whereClause = static::getCustomWhereClause();
        if (! empty($conditions)) {
            $whereClause .= ($whereClause ? ' AND ' : 'WHERE ').implode(' AND ', $conditions);
        }

        $columns = static::getCustomSelectColumns();

        // Paginación por clave (?after_id=): usa el índice de la PK, cuesta lo
        // mismo en la página 1 que en la 5000 y es estable entre páginas. La
        // paginación por offset se mantiene tal cual para los clientes actuales.
        if ($afterId !== null && $afterId !== '') {
            $pk = strtolower($instance->getKeyName());
            $params['after_id'] = $afterId;
            $keysetWhere = ($whereClause ? $whereClause.' AND ' : 'WHERE ')."t.$pk > :after_id";

            $sql = "SELECT * FROM (
                        SELECT /*+ FIRST_ROWS($limit) */ $columns
                        FROM DEVELOPER.$table t
                        $keysetWhere
                        ORDER BY t.$pk
                    ) WHERE ROWNUM <= ".($limit + 1);

            $results = static::hydrateLobColumns($oci8, $oci8->query($sql, $params));
            $hasMore = count($results) > $limit;
            if ($hasMore) {
                $results = array_slice($results, 0, $limit);
            }
            $last = end($results);

            $response = [
                'success' => true,
                'data' => $results,
                'pagination' => [
                    'limit' => $limit,
                    'after_id' => $afterId,
                    'next_after_id' => $hasMore && $last ? $last[$pk] : null,
                    'count' => count($results),
                    'hasMore' => $hasMore,
                ],
            ];

            if ($useCache) {
                cache()->put($cacheKey, $response, 60);
            }

            return $response;
        }

        // Determine if we need WHERE or AND before ROWNUM
        $hasWhere = ! empty($whereClause);
        $rownumKeyword = $hasWhere ? 'AND' : 'WHERE';

        // Sin RESULT_CACHE: con offsets y filtros variables casi nunca se
        // reutilizaba y llenaba el result cache del servidor Oracle, que es
        // compartido con el resto del ERP. Las columnas se respetan también
        // con offset (antes el subquery hacía t.* e ignoraba las del modelo).
        if ($offset === 0) {
            $sql = "SELECT /*+ FIRST_ROWS($limit) */ $columns
                    FROM DEVELOPER.$table t
                    $whereClause
                    $rownumKeyword ROWNUM <= ".($limit + 1);
        } else {
            // El subquery solo recorre ROWID: las columnas (y los CLOB) se leen
            // únicamente para las filas de la página, no para las $offset previas.
            // FULL(t): mismo recorrido que la página 0; sin ORDER BY, ROWNUM
            // sigue el plan, y con otro plan las páginas se desalineaban.
            $sql = "SELECT /*+ FIRST_ROWS($limit) */ $columns
                    FROM DEVELOPER.$table t
                    JOIN (
                        SELECT /*+ FULL(t) */ t.ROWID AS rid, ROWNUM AS rn
                        FROM DEVELOPER.$table t
                        $whereClause
                        $rownumKeyword ROWNUM <= ".($offset + $limit + 1)."
                    ) p ON t.ROWID = p.rid
                    WHERE p.rn > $offset
                    ORDER BY p.rn";
        }

        $results = static::hydrateLobColumns($oci8, $oci8->query($sql, $params));
        $hasMore = count($results) > $limit;

        if ($hasMore) {
            $results = array_slice($results, 0, $limit);
        }

        // Remove ROWNUM
        $results = array_map(function ($row) {
            unset($row['rn']);

            return $row;
        }, $results);

        $response = [
            'success' => true,
            'data' => $results,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($results),
                'hasMore' => $hasMore,
            ],
        ];

        // Cache result for 60 seconds
        if ($useCache) {
            cache()->put($cacheKey, $response, 60);
        }

        return $response;
    }

    /**
     * Columnas CLOB que el modelo selecciona en línea con lobSelect() en su
     * getCustomSelectColumns(). Leer un CLOB como LOB cuesta una ida y vuelta
     * por fila (MODELO: 2,8 s para 20 filas frente a 40 ms).
     *
     * @return array<int, string>
     */
    protected static function oci8LobColumns(): array
    {
        return [];
    }

    /**
     * Fragmento SELECT que trae un CLOB como VARCHAR2 más su longitud, para
     * completar después solo los que no caben. La BD usa WE8MSWIN1252
     * (1 byte por carácter), así que 4000 caracteres caben en un VARCHAR2.
     */
    protected static function lobSelect(string $column): string
    {
        return "DBMS_LOB.SUBSTR(t.$column, 4000, 1) AS $column, DBMS_LOB.GETLENGTH(t.$column) AS {$column}__len";
    }

    /**
     * Completa los CLOB de más de 4000 caracteres (una consulta por columna,
     * solo para esas filas) y conserva '' para los EMPTY_CLOB.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected static function hydrateLobColumns(OCI8Service $oci8, array $rows): array
    {
        $lobColumns = static::oci8LobColumns();
        if ($lobColumns === [] || $rows === []) {
            return $rows;
        }

        $instance = new static;
        $pk = strtolower($instance->getKeyName());
        $table = $instance->getTable();

        foreach ($lobColumns as $col) {
            $lenKey = $col.'__len';
            $long = [];
            foreach ($rows as $i => $row) {
                $len = $row[$lenKey] ?? null;
                if ($len !== null && (int) $len > 4000) {
                    $long[$row[$pk]] = $i;
                } elseif ($len !== null && (int) $len === 0) {
                    $rows[$i][$col] = '';
                }
                unset($rows[$i][$lenKey]);
            }

            if ($long !== []) {
                $binds = [];
                foreach (array_keys($long) as $n => $id) {
                    $binds['id'.$n] = $id;
                }
                $in = implode(',', array_map(fn ($k) => ':'.$k, array_keys($binds)));
                foreach ($oci8->query("SELECT $pk, $col FROM DEVELOPER.$table WHERE $pk IN ($in)", $binds) as $full) {
                    $rows[$long[$full[$pk]]][$col] = $full[$col];
                }
            }
        }

        return $rows;
    }

    /**
     * WHERE clause personalizado (override en el modelo si necesario)
     */
    protected static function getCustomWhereClause(): string
    {
        $instance = new static;

        // Check if model uses SoftDeletes
        if (method_exists($instance, 'getDeletedAtColumn')) {
            $deletedAt = $instance->getDeletedAtColumn();

            return "WHERE t.$deletedAt IS NULL";
        }

        return '';
    }

    /**
     * Columnas a seleccionar (override en el modelo si necesario)
     */
    protected static function getCustomSelectColumns(): string
    {
        return 't.*';
    }
}
