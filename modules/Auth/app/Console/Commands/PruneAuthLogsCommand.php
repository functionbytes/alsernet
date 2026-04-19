<?php

namespace Modules\Auth\Console\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Models\ImpersonationLog;
use Modules\Auth\Models\LoginAttempt;

/**
 * Prune old login_attempts and impersonation_logs rows to keep tables lean.
 *
 * Usage:
 *   php artisan auth:prune                 # default: 90 days
 *   php artisan auth:prune --days=30       # custom retention
 *   php artisan auth:prune --dry-run       # preview without deleting
 */
class PruneAuthLogsCommand extends Command
{
    protected $signature = 'auth:prune
                            {--days=90 : Retention window in days}
                            {--dry-run : Show how many rows would be deleted without deleting}';

    protected $description = 'Prune old login attempts and impersonation logs';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('El parámetro --days debe ser >= 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Cutoff: {$cutoff->toDateTimeString()} ({$days} días de retención)");

        $attempts = LoginAttempt::where('attempted_at', '<', $cutoff)->count();
        $impersonations = ImpersonationLog::where('started_at', '<', $cutoff)->count();

        $this->line("  Login attempts a purgar:   {$attempts}");
        $this->line("  Impersonations a purgar:   {$impersonations}");

        if ($dryRun) {
            $this->warn('Modo dry-run: no se eliminó nada.');

            return self::SUCCESS;
        }

        LoginAttempt::where('attempted_at', '<', $cutoff)->delete();
        ImpersonationLog::where('started_at', '<', $cutoff)->delete();

        $this->info('Registros eliminados correctamente.');

        return self::SUCCESS;
    }
}
