<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Artisan;
use Cache;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Modules\Mailing\Helpers\LicenseHelper;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Library\UpgradeManager;
use Modules\Mailing\Models\FailedJob;
use Modules\Mailing\Models\Job;
use Modules\Mailing\Models\JobMonitor;
use Modules\Mailing\Models\Language;
use Modules\Mailing\Models\Notification;
use Modules\Mailing\Models\Plugin;
use Modules\Mailing\Models\Setting;
use Modules\Mailing\Models\Template;

class Upgrade extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function finalize(Request $request)
    {
        Artisan::call('route:clear');
        // Artisan::call('cache:clear'); // ==> it will clean up all statistics
        Artisan::call('view:clear');
        Setting::writeDefaultSettings();
        JobMonitor::query()->delete();
        Job::query()->delete();
        FailedJob::query()->delete();
        try {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropIndex(['queue']);
            });
        } catch (Exception $ex) {
            // Index already dropped
        }

        // Do not clear cache, it will clean up all statistics
        // Cache::flush();

        Language::dump();
        Template::resetDefaultTemplates();
        Template::resetPopupTemplates();
        Artisan::call('queue:prune-batches --hours=24 --unfinished=-1'); // clean up any pending job batches
        Artisan::call('queue:restart');

        // Reset GEODB
        if (Setting::get('geoip.sqlite.dbname') != 'storage/app/GeoLite2-City.mmdb') {
            Setting::set('geoip.sqlite.dbname', 'storage/app/GeoLite2-City.mmdb');
            Setting::set('geoip.sqlite.source_url', 'https://acellemail.s3.amazonaws.com/GeoLite2-City.mmdb');
            Setting::set('geoip.sqlite.source_hash', 'b95ecaff82017c4f52577196c41db946');
            Setting::set('geoip.enabled', 'no');
        }

        // New way to load plugins: initialize the plugin index file
        Plugin::resetPluginMasterFile();

        // Merge .env.example to .env
        $manager = new UpgradeManager;
        $manager->mergeEnv(base_path('.env.example'));

        // Clean up all notifications
        Notification::truncate();

        // Refresh license
        LicenseHelper::refreshLicense();

        // Upgrade post-processing
        if ($request->session()->has('upgraded')) {
            $request->session()->forget('upgraded');
            Session::save();

            // Redirect with success message
            $request->session()->flash('alert-success', trans('messages.upgrade.alert.upgrade_success'));

            return redirect()->action('Settings\SettingController@upgrade');
        } else {
            echo '<html>
                <head>
                    <meta http-equiv="refresh" content="3;'.url('/').'" />
                    <title>Finalization</title>
                </head>
                <body>
                    Finalization done! redirecting...
                </body>
            </html>';
        }
    }

    public function migrate(Request $request)
    {
        Artisan::call('migrate', ['--force' => true]);
        sleep(3);
        if ($request->session()->has('upgraded')) {
            $nextPage = url('/admin/upgrade/finalize');
            echo "Finalization in progress...<meta http-equiv=\"refresh\" content=\"5;URL='".$nextPage."'\" />";
        } else {
            echo "Migration done! Redirecting...<meta http-equiv=\"refresh\" content=\"5;URL='".url('/')."'\" />";
        }
    }

    public function dropIndex()
    {
        try {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropIndex(['queue']);
            });

            return response('Dropped');
        } catch (Exception $ex) {
            return response('Dropped! '.$ex->getMessage());
        }
    }

    public function createIndex()
    {
        try {
            Schema::table('jobs', function (Blueprint $table) {
                $table->index(['queue']);
            });

            return response('Done!');
        } catch (Exception $ex) {
            return response('Done! '.$ex->getMessage());
        }
    }
}
