<?php

namespace App\Console;

use App\Library\Facades\SubscriptionFacade;
use App\Models\Automation\Automation;
use App\Models\Notification;
use App\Events\CronJobExecuted;
use App\Models\Campaign\Campaign;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {

        if (!isInitiated()) {
            return;
        }

        // Make sure CLI process is NOT executed as root
        Notification::recordIfFails(function () {
            if (!exec_enabled()) {
                throw new Exception('The exec() function is missing or disabled on the hosting server');
            }

            if (exec('whoami') == 'root') {
                throw new Exception("Cronjob process is executed by 'root' which might cause permission issues. Make sure the cronjob process owner is the same as the acellemail/ folder's owner");
            }
        }, 'CronJob issue');

        $schedule->call(function () {
            event(new CronJobExecuted());
        })->name('cronjob_event:log')->everyMinute();

        // Automation2
        $schedule->call(function () {
            Automation::run();
        })->name('automation:run')->everyFiveMinutes();

        // Bounce/feedback handler
        $schedule->command('handler:run')->everyThirtyMinutes();

        // Queued import/export/campaign
        // Allow overlapping: max 10 proccess as a given time (if cronjob interval is every minute)
        // Job is killed after timeout
        //$schedule->command('queue:work --queue=default,batch --timeout=120 --tries=1 --max-time=180')->everyMinute();

        // Make it more likely to have a running queue at any given time
        // Make sure it is stopped before another queue listener is created
        // $schedule->command('queue:work --queue=default,batch --timeout=120 --tries=1 --max-time=290')->everyFiveMinutes();

        // Sender verifying
        $schedule->command('sender:verify')->everyFiveMinutes();

        // System clean up
        $schedule->command('system:cleanup')->daily();

        // GeoIp database check
        $schedule->command('geoip:check')->everyMinute()->withoutOverlapping(60);

        // Check for scheduled campaign to execute
        $schedule->call(function () {
            Campaign::checkAndExecuteScheduledCampaigns();
        })->name('check_and_execute_scheduled_campaigns')->everyMinute();



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
