<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {

        $schedule->command('imap:emailticket')->everyMinute();
        $schedule->command('ticket:autoclose')->everyMinute();
        $schedule->command('ticket:autooverdue')->everyMinute();
        $schedule->command('ticket:autoresponseticket')->everyMinute();
        $schedule->command('notification:autodelete')->everyMinute();
        $schedule->command('trashedticket:autodelete')->everyMinute();
        $schedule->command('livechat:AutoSolve')->everyMinute();
        $schedule->command('disposable:update')->weekly();
        $schedule->command('customer:inactive_delete')->everyMinute();
        $schedule->command('cache:clear')->everyThirtyMinutes();
        $schedule->command('config:clear')->everyThirtyMinutes();
        $schedule->command('route:clear')->everyThirtyMinutes();
        $schedule->command('optimize:clear')->everyThirtyMinutes();
        $schedule->command('view:clear')->everyThirtyMinutes();
        //$schedule->command('Dataseed:updating')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
