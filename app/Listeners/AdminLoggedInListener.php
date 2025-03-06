<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Library\Notification\SystemUrl;
use App\Library\Notification\CronJob;
use App\Events\AdminLoggedIn;
use App\Models\Notification;
class AdminLoggedInListener
{
    public function __construct()
    {
        //
    }

    public function handle(AdminLoggedIn $event)
    {
        CronJob::check();
        SystemUrl::check();
        $this->checkForPhpVersion();
    }

    public function checkForPhpVersion()
    {
        $title = 'PHP version is no longer supported';

        if (version_compare(PHP_VERSION, config('custom.php_recommended'), '<')) {
            Notification::error([
                'title' => $title,
                'message' => sprintf("Your hosting server's PHP version %s is no longer supported, please upgrade to version %s or higher", PHP_VERSION, config('custom.php_recommended')),
            ]);
        } else {
            Notification::cleanupDuplicateNotifications($title);
        }
    }

}
