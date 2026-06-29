<?php

namespace Modules\Page\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Page\Services\PageCacheService;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(Schedule::class)->call(function () {
            // Warm page cache every 6 hours
            PageCacheService::warmPopular(20);
        })->everyFourHours()->between('00:00', '23:59')->withoutOverlapping();
    }
}
