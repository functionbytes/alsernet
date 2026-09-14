<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Services\OCI8Service;

class CacheCustomerOrders extends Command
{
    protected $signature = 'erp:cache-customer-orders
                            {id : Customer ID}
                            {--status= : Filter by order status}
                            {--from= : Filter from date (YYYY-MM-DD)}
                            {--to= : Filter to date (YYYY-MM-DD)}
                            {--offset=0 : Pagination offset}
                            {--limit=10 : Pagination limit}';

    protected $description = 'Fetch and cache orders for a customer from PEDIDOCLI_CENTRAL (runs slow full-scan query in background)';

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $limit = min((int) ($this->option('limit') ?: 10), 100);
        $offset = (int) ($this->option('offset') ?: 0);
        $status = (string) ($this->option('status') ?: '');
        $from = (string) ($this->option('from') ?: '');
        $to = (string) ($this->option('to') ?: '');

        $cacheKey = "customer:orders:{$id}:s{$status}:f{$from}:t{$to}:off{$offset}:lim{$limit}";

        if (cache()->has($cacheKey)) {
            return self::SUCCESS;
        }

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
        $cols = 'IDPEDIDOCLI_CENTRAL, IDPEDIDOCLI, IDCLIENTE, IDALMACEN, ESTADO, NPEDIDOCLI, '
            ."TO_CHAR(FPEDIDO, 'YYYY-MM-DD HH24:MI:SS') AS FPEDIDO, "
            ."TO_CHAR(FPREVISTA, 'YYYY-MM-DD') AS FPREVISTA, "
            ."TO_CHAR(FSERVIDO, 'YYYY-MM-DD HH24:MI:SS') AS FSERVIDO, "
            .'OBSERVACIONES, TIPOPEDIDO, IDORIGENPEDIDOCLI, '
            ."TO_CHAR(FCREACION, 'YYYY-MM-DD HH24:MI:SS') AS FCREACION, "
            ."TO_CHAR(FMODIFICACION, 'YYYY-MM-DD HH24:MI:SS') AS FMODIFICACION";

        $totalLimit = $offset + $limit + 1;

        if ($offset === 0) {
            $sql = "SELECT {$cols} FROM DEVELOPER.PEDIDOCLI_CENTRAL {$where} AND ROWNUM <= {$totalLimit}";
        } else {
            $sql = 'SELECT * FROM (SELECT t1.*, ROWNUM AS rn FROM ('
                ."SELECT {$cols} FROM DEVELOPER.PEDIDOCLI_CENTRAL {$where}"
                .") t1 WHERE ROWNUM <= {$totalLimit}) WHERE rn > {$offset}";
        }

        try {
            $rows = app(OCI8Service::class)->query($sql, $bindings);
            cache()->put($cacheKey, $rows, now()->addHour());
            Log::info("erp:cache-customer-orders: cached customer {$id}, ".count($rows).' rows');
        } catch (\Exception $e) {
            Log::error("erp:cache-customer-orders: failed for customer {$id}", ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
