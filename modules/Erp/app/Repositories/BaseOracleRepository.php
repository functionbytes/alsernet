<?php

namespace Modules\Erp\Repositories;

use Illuminate\Support\Collection;
use Modules\Erp\Services\OCI8Service;

abstract class BaseOracleRepository
{
    protected OCI8Service $oci8;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct(OCI8Service $oci8)
    {
        $this->oci8 = $oci8;
    }

    /**
     * Find records with pagination
     */
    public function paginate(array $filters = [], int $limit = 10, int $offset = 0): array
    {
        // Defensive casts: callers pass paginator input that may be a string.
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $conditions = [];
        $params = [];

        $this->buildFilters($filters, $conditions, $params);

        $whereClause = $this->buildWhereClause($conditions);
        $columns = $this->getSelectColumns();

        // Oracle 11g optimized pagination with FIRST_ROWS hint
        if ($offset === 0) {
            // Optimized query for first page (no subquery needed)
            $sql = "SELECT /*+ FIRST_ROWS({$limit}) */ {$columns}
                    FROM DEVELOPER.{$this->table} t
                    {$whereClause}
                    AND ROWNUM <= ".($limit + 1);
        } else {
            // Use subquery only when offset > 0
            $sql = "SELECT /*+ FIRST_ROWS({$limit}) */ {$columns}
                    FROM (
                        SELECT t.*, ROWNUM as rn
                        FROM DEVELOPER.{$this->table} t
                        {$whereClause}
                        AND ROWNUM <= ".($offset + $limit + 1).'
                    )
                    WHERE rn > '.$offset;
        }

        $results = $this->oci8->query($sql, $params);
        $hasMore = count($results) > $limit;

        if ($hasMore) {
            $results = array_slice($results, 0, $limit);
        }

        // Remove ROWNUM column if present
        $results = array_map(function($row) {
            unset($row['RN']);
            return $row;
        }, $results);

        return [
            'data' => $results,
            'hasMore' => $hasMore,
            'count' => count($results),
        ];
    }

    /**
     * Get columns to select (can be overridden for specific columns)
     */
    protected function getSelectColumns(): string
    {
        return 't.*';
    }

    /**
     * Build WHERE clause from conditions
     */
    protected function buildWhereClause(array $conditions): string
    {
        if (empty($conditions)) {
            return $this->getDefaultWhereClause();
        }

        return $this->getDefaultWhereClause() . ' AND ' . implode(' AND ', $conditions);
    }

    /**
     * Default WHERE clause (can be overridden)
     */
    protected function getDefaultWhereClause(): string
    {
        return 'WHERE 1=1';
    }

    /**
     * Build filters (must be implemented by child classes)
     */
    abstract protected function buildFilters(array $filters, array &$conditions, array &$params): void;
}
