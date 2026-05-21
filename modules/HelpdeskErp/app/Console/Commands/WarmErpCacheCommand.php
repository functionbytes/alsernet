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

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $emails = $this->collectEmails($limit);

        $this->info('Emails a pre-cachear: '.count($emails));

        if (empty($emails)) {
            return self::SUCCESS;
        }

        foreach (array_chunk($emails, 25) as $chunk) {
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
            if (! Schema::hasTable($candidate['table'])) {
                continue;
            }

            if (! Schema::hasColumn($candidate['table'], $candidate['col'])) {
                continue;
            }

            try {
                return DB::table($candidate['table'])
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
