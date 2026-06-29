<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Seo\Jobs\CleanupOld404Logs;
use Modules\Seo\Jobs\CleanupOldAuditLogs;

class SeoCleanupCommand extends Command
{
    protected $signature = 'seo:cleanup {--all : Run all cleanup jobs} {--404 : Clean 404 logs only} {--audit : Clean audit logs only}';

    protected $description = 'Run SEO cleanup jobs (404 logs, audit logs)';

    public function handle(): int
    {
        if (! $this->option('all') && ! $this->option('404') && ! $this->option('audit')) {
            $this->warn('Specify --all, --404, or --audit. Use php artisan seo:cleanup --all to run everything.');

            return self::FAILURE;
        }

        if ($this->option('all') || $this->option('404')) {
            CleanupOld404Logs::dispatch();
            $this->info('404 log cleanup job dispatched.');
        }

        if ($this->option('all') || $this->option('audit')) {
            CleanupOldAuditLogs::dispatch();
            $this->info('Audit log cleanup job dispatched.');
        }

        return self::SUCCESS;
    }
}
