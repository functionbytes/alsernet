<?php

namespace Modules\Mailing\Listeners;

use Modules\Mailing\Events\AdminLoggedIn;
use Modules\Mailing\Library\Notification\CronJob;
use Modules\Mailing\Library\Notification\SystemUrl;
use Modules\Mailing\Models\Notification;

class AdminLoggedInListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(AdminLoggedIn $event)
    {
        // Check CronJob
        CronJob::check();

        // Check System URL
        SystemUrl::check();

        // Check for PHP version
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
