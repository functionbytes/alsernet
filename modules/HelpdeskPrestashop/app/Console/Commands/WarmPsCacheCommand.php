<?php

namespace Modules\HelpdeskPrestashop\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\HelpdeskPrestashop\Jobs\WarmPsCacheJob;

class WarmPsCacheCommand extends Command
{
    protected $signature = 'helpdeskprestashop:warm-cache {--limit=200}';

    protected $description = 'Pre-poblar caché PrestaShop para conversaciones/tickets abiertos';

    public function handle(): int
    {
        $emails = $this->collectEmails((int) $this->option('limit'));

        $this->info('Emails a pre-cachear: '.count($emails));

        if (empty($emails)) {
            return self::SUCCESS;
        }

        foreach (array_chunk($emails, 50) as $chunk) {
            WarmPsCacheJob::dispatch($chunk);
        }

        $this->info('Jobs encolados.');

        return self::SUCCESS;
    }

    private function collectEmails(int $limit): array
    {
        $candidates = [
            ['table' => 'helpdesk_conversations', 'col' => 'email'],
            ['table' => 'helpdesk_conversations', 'col' => 'from_email'],
            ['table' => 'helpdesk_tickets', 'col' => 'requester_email'],
            ['table' => 'helpdesk_tickets', 'col' => 'email'],
        ];

        foreach ($candidates as $candidate) {
            try {
                if (! Schema::hasTable($candidate['table']) || ! Schema::hasColumn($candidate['table'], $candidate['col'])) {
                    continue;
                }

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
