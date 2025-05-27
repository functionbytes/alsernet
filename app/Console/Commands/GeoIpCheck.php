<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as LaravelLog;
use App;
use Acelle\Model\Setting;
use Acelle\Model\Notification;
use Exception;
use Acelle\Library\Lockable;

class GeoIpCheck extends Command
{

    protected $signature = 'geoip:check';

    protected $description = 'Check the current GeoIp service';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $timeoutCallback = function () {
        };

        $lock = new Lockable(storage_path('locks/geoip-setup'));
        $lock->getExclusiveLock(function () {
            $this->check();
        }, $timeout = 5, $timeoutCallback);

        return 0;
    }

    public function check()
    {
        $geoip = App::make('Acelle\Library\Contracts\GeoIpInterface');

        if (Setting::get('geoip.enabled') == 'installing') {
            LaravelLog::info('GeoIP installation is already in progress');
            return;
        }

        if (Setting::isYes('geoip.enabled')) {
            return;
        }

        Setting::set('geoip.enabled', 'installing');

        Notification::warning([
            'title' => 'GeoIP setup',
            'message' => 'GeoIP database is being installed in the background. Process '.getmypid().' started at '.date("M-d-Y H:i:s")]);

        LaravelLog::info('Setting up GeoIP database');

        try {
            $geoip->setup();
            Setting::setYes('geoip.enabled');
            Notification::cleanupDuplicateNotifications('GeoIP setup');
            LaravelLog::info('GeoIP database is successfully installed');
        } catch (Exception $ex) {
            LaravelLog::error('Installing GeoIp database failed');
            Notification::warning([
                'title' => 'GeoIP setup',
                'message' => 'Cannot install GeoIp database. Error: '.$ex->getMessage(),
            ]);

            throw $ex;
        }
    }

}
