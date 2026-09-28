<?php

namespace Modules\Erp\Support;

/**
 * SQL del listado de pedidos de un cliente (PEDIDOCLI_CENTRAL).
 *
 * Compartido por CustomerCommercialController::orders() y el comando
 * erp:cache-customer-orders, que precalienta la misma clave de caché.
 *
 * Ordena por FPEDIDO DESC: antes era `ROWNUM <= N` sin ORDER BY y Oracle
 * devolvía N pedidos cualesquiera (en la práctica, los más ANTIGUOS: para
 * un cliente con pedidos en 2026 salían los de 2019).
 */
final class CustomerOrdersQuery
{
    /**
     * Versión de la clave de caché: sube al cambiar el SQL o el orden para
     * no servir resultados cacheados con el criterio anterior.
     */
    private const CACHE_VERSION = 'v2';

    private const COLUMNS = 'IDPEDIDOCLI_CENTRAL, IDPEDIDOCLI, IDCLIENTE, IDALMACEN, ESTADO, NPEDIDOCLI, '
        ."TO_CHAR(FPEDIDO, 'YYYY-MM-DD HH24:MI:SS') AS FPEDIDO, "
        ."TO_CHAR(FPREVISTA, 'YYYY-MM-DD') AS FPREVISTA, "
        ."TO_CHAR(FSERVIDO, 'YYYY-MM-DD HH24:MI:SS') AS FSERVIDO, "
        .'OBSERVACIONES, TIPOPEDIDO, IDORIGENPEDIDOCLI, '
        ."TO_CHAR(FCREACION, 'YYYY-MM-DD HH24:MI:SS') AS FCREACION, "
        ."TO_CHAR(FMODIFICACION, 'YYYY-MM-DD HH24:MI:SS') AS FMODIFICACION";

    public static function cacheKey(int $id, string $status, string $from, string $to, int $offset, int $limit): string
    {
        return 'customer:orders:'.self::CACHE_VERSION.":{$id}:s{$status}:f{$from}:t{$to}:off{$offset}:lim{$limit}";
    }

    /**
     * Pide $offset + $limit + 1 filas para deducir hasMore sin COUNT(*).
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    public static function build(int $id, string $status, string $from, string $to, int $offset, int $limit): array
    {
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
        $totalLimit = $offset + $limit + 1;

        // El ORDER BY va DENTRO y ROWNUM fuera: aplicado al revés, Oracle
        // corta las N primeras filas que encuentra y después las ordena.
        $ordered = 'SELECT '.self::COLUMNS." FROM DEVELOPER.PEDIDOCLI_CENTRAL {$where} "
            .'ORDER BY PEDIDOCLI_CENTRAL.FPEDIDO DESC NULLS LAST, IDPEDIDOCLI_CENTRAL DESC';

        $sql = $offset === 0
            ? "SELECT * FROM ({$ordered}) WHERE ROWNUM <= {$totalLimit}"
            : "SELECT * FROM (SELECT t1.*, ROWNUM AS rn FROM ({$ordered}) t1 WHERE ROWNUM <= {$totalLimit}) WHERE rn > {$offset}";

        return [$sql, $bindings];
    }
}
