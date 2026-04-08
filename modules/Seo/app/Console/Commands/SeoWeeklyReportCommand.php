<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Seo\Jobs\SendSeoWeeklyReport;

class SeoWeeklyReportCommand extends Command
{
    protected $signature = 'seo:weekly-report {--email= : Override recipient email}';

    protected $description = 'Send the weekly SEO report email';

    public function handle(): int
    {
        $email = $this->option('email') ?: config('Seo.notifications.email');

        if (! $email) {
            $this->error('No notification email configured. Set SEO_NOTIFICATION_EMAIL in .env or pass --email=');

            return self::FAILURE;
        }

        SendSeoWeeklyReport::dispatch();
        $this->info("Weekly SEO report queued for: {$email}");

        return self::SUCCESS;
    }
}
