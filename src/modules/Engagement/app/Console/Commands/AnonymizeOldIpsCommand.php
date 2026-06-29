<?php

declare(strict_types=1);

namespace Modules\Engagement\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnonymizeOldIpsCommand extends Command
{
    protected $signature = 'engagement:anonymize-old-ips {--days=30 : Anonymise IPs older than this many days}';

    protected $description = 'Anonymises IP addresses in visitor sessions and audit logs older than N days (GDPR compliance)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $total = 0;

        foreach (['engagement_visitor_sessions', 'engagement_audit_logs'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'ip_address')) {
                continue;
            }

            $count = DB::table($table)
                ->where('created_at', '<', $cutoff)
                ->where('ip_address', '!=', '0.0.0.0')
                ->update(['ip_address' => '0.0.0.0']);

            $this->info("{$table}: {$count} IPs anonimizadas");
            $total += $count;
        }

        $this->info("Total: {$total} registros actualizados (corte: {$cutoff->toDateString()})");

        return self::SUCCESS;
    }
}
