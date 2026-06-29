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

        if ($emails === []) {
            return self::SUCCESS;
        }

        foreach (array_chunk($emails, 50) as $chunk) {
            WarmPsCacheJob::dispatch($chunk);
        }

        $this->info('Jobs encolados.');

        return self::SUCCESS;
    }

    /**
     * Collects emails from every available helpdesk source table, deduplicates,
     * and trims to the requested limit. Reading from all sources avoids the
     * previous behaviour where only the first matching table fed the warm-up.
     */
    private function collectEmails(int $limit): array
    {
        $candidates = [
            ['table' => 'helpdesk_conversations', 'col' => 'email'],
            ['table' => 'helpdesk_conversations', 'col' => 'from_email'],
            ['table' => 'helpdesk_tickets', 'col' => 'requester_email'],
            ['table' => 'helpdesk_tickets', 'col' => 'email'],
        ];

        $collected = [];

        foreach ($candidates as $candidate) {
            try {
                if (! Schema::hasTable($candidate['table']) || ! Schema::hasColumn($candidate['table'], $candidate['col'])) {
                    continue;
                }

                $rows = DB::table($candidate['table'])
                    ->select($candidate['col'].' as email', 'updated_at')
                    ->whereNotNull($candidate['col'])
                    ->where($candidate['col'], 'like', '%@%')
                    ->orderByDesc('updated_at')
                    ->limit($limit)
                    ->get();

                foreach ($rows as $row) {
                    $email = strtolower(trim((string) $row->email));

                    if ($email === '') {
                        continue;
                    }

                    // Keep the most recent updated_at when the same email appears
                    // in several tables.
                    if (! isset($collected[$email]) || $row->updated_at > $collected[$email]) {
                        $collected[$email] = $row->updated_at;
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        arsort($collected);

        return array_slice(array_keys($collected), 0, $limit);
    }
}
