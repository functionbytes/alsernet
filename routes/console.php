<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\HelpdeskSocial\Jobs\CalculateSocialMetricsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backups diarios con Spatie Laravel Backup
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('02:00');
Schedule::command('backup:monitor')->daily()->at('03:00');

// HelpdeskSocial: sincronización de comentarios cada 15 minutos
Schedule::command('helpdesk-social:sync-comments')
    ->everyFifteenMinutes()
    ->onOneServer()
    ->withoutOverlapping(30)
    ->runInBackground()
    ->when(fn () => helpdesk_social_enabled());

// HelpdeskSocial: detección de incumplimientos de SLA cada 5 minutos
Schedule::command('helpdesksocial:check-sla-breaches --notify')
    ->everyFiveMinutes()
    ->onOneServer()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->when(fn () => helpdesk_social_enabled());

// HelpdeskSocial: health check diario a las 08:00
Schedule::command('helpdesk-social:health-check --notify')
    ->dailyAt('08:00')
    ->onOneServer()
    ->withoutOverlapping(30)
    ->when(fn () => helpdesk_social_enabled());

// HelpdeskSocial: cálculo de métricas diarias
Schedule::job(new CalculateSocialMetricsJob(now()->subDay()->toDateString()))
    ->dailyAt('06:00')
    ->onOneServer()
    ->when(fn () => helpdesk_social_enabled());
