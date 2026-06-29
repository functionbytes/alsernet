<?php

namespace Modules\Cookie\Console\Commands;

use Illuminate\Console\Command;
use Modules\Cookie\Models\CookieConsentLog;
use Modules\Core\Models\Setting;

class CleanupCookieLogsCommand extends Command
{
    protected $signature = 'cookie:cleanup-logs {--days=90 : Días a retener}';

    protected $description = 'Elimina registros de consentimiento más antiguos que N días';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? Setting::get('cookie.log_retention_days', 90));

        $cutoff = now()->subDays($days);
        $deleted = CookieConsentLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Eliminados {$deleted} registros anteriores a {$cutoff->toDateString()}");

        return self::SUCCESS;
    }
}
