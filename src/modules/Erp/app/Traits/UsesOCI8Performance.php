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
    public static function fastPaginate(array $filters = [], int $limit = 10, int $offset = 0, ?bool $useCache = null): array
    {
        $instance = new static;

        // Read cache setting from database if not explicitly set
        if ($useCache === null) {
            $settings = Setting::getErpSettings();
            $useCache = $settings['oracle_enable_cache'] ?? true;
        }

        // Cache key for Redis
        if ($useCache) {
            $cacheKey = 'erp:'.$instance->getTable().':'.md5(json_encode($filters).$limit.$offset);

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

        // Determine if we need WHERE or AND before ROWNUM
        $hasWhere = ! empty($whereClause);
        $rownumKeyword = $hasWhere ? 'AND' : 'WHERE';

        // Optimized query with Oracle hints
        if ($offset === 0) {
            $sql = "SELECT /*+ RESULT_CACHE FIRST_ROWS($limit) */ $columns
                    FROM DEVELOPER.$table t
                    $whereClause
                    $rownumKeyword ROWNUM <= ".($limit + 1);
        } else {
            $sql = "SELECT /*+ RESULT_CACHE FIRST_ROWS($limit) */ *
                    FROM (
                        SELECT /*+ RESULT_CACHE */ t.*, ROWNUM as rn
                        FROM DEVELOPER.$table t
                        $whereClause
                        $rownumKeyword ROWNUM <= ".($offset + $limit + 1)."
                    )
                    WHERE rn > $offset";
        }

        $results = $oci8->query($sql, $params);
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

        // Cache result: catalog data (familias/subfamilias/grupos) is near-static,
        // so a longer TTL raises the hit ratio dramatically versus the old 60s that
        // expired almost as fast as it filled. Invalidate proactively from
        // MonitorOracleChanges when a real change is detected.
        if ($useCache) {
            cache()->put($cacheKey, $response, 900);
        }

        return $response;
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
