<?php

namespace Modules\HelpdeskErp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\HelpdeskErp\Jobs\WarmErpCacheJob;

class WarmErpCacheCommand extends Command
{
    protected $signature = 'helpdeskerp:warm-cache {--limit=200}';

    protected $description = 'Pre-poblar caché ERP para conversaciones y tickets abiertos';

    /**
     * Emails por job. Debe cumplir CHUNK_SIZE × helpdeskErp.http_timeout <
     * WarmErpCacheJob::$timeout con margen real (no solo "no reventar por un
     * pelo"): con el manager ERP caído, cada email cuelga hasta http_timeout
     * antes de que el circuit breaker intervenga (ver circuit_open_seconds en
     * config/config.php). Bug real 4-sep-2026: con 5 en vez de 3, un chunk
     * entero de fallos necesitaba hasta 5×15=75s, por encima del timeout del
     * propio job (60s) — reventaba por su propio timeout ANTES de que el
     * breaker llegara a abrirse, monopolizando el único worker que atiende
     * 'notifications' (ver reference_helpdesk_erp_warmcache_infinite_loop).
     */
    private const CHUNK_SIZE = 3;

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $emails = $this->collectEmails($limit);

        $this->info('Emails a pre-cachear: '.count($emails));

        if (empty($emails)) {
            return self::SUCCESS;
        }

        foreach (array_chunk($emails, self::CHUNK_SIZE) as $chunk) {
            WarmErpCacheJob::dispatch($chunk);
        }

        $this->info('Jobs encolados en queue helpdesk-erp-warming.');

        return self::SUCCESS;
    }

    /**
     * Devuelve emails únicos de la primera tabla candidata que exista y tenga datos.
     *
     * @return array<string>
     */
    private function collectEmails(int $limit): array
    {
        $candidates = [
            ['table' => 'helpdesk_customers', 'col' => 'email'],
            ['table' => 'helpdesk_conversations', 'col' => 'email'],
            ['table' => 'helpdesk_conversations', 'col' => 'from_email'],
            ['table' => 'helpdesk_tickets', 'col' => 'requester_email'],
            ['table' => 'helpdesk_tickets', 'col' => 'email'],
        ];

        foreach ($candidates as $candidate) {
            if (! Schema::connection('helpdesk')->hasTable($candidate['table'])) {
                continue;
            }

            if (! Schema::connection('helpdesk')->hasColumn($candidate['table'], $candidate['col'])) {
                continue;
            }

            try {
                return DB::connection('helpdesk')->table($candidate['table'])
                    ->whereNotNull($candidate['col'])
                    ->where($candidate['col'], 'like', '%@%')
                    ->orderByDesc('updated_at')
                    ->limit($limit)
                    ->pluck($candidate['col'])
                    ->unique()
                    ->values()
                    ->all();
            } catch (\Throwable) {
                continue;
            }
        }

        return [];
    }
}
