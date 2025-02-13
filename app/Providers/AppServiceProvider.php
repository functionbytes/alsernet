<?php

namespace App\Providers;

use App\Library\Facades\Hook;
use App\Models\Setting\Setting;
use Illuminate\Support\Facades\URL;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function register(){

        view()->composer("*",function($view){
            $view->with("setting", $this->setting());
        });


    }

    public function setting()
    {
        return Setting::first();
    }


    public function boot()
    {
        Schema::defaultStringLength(191);

        $this->changeDefaultSettings();

            config(['websockets.dashboard.port' => setting('liveChatPort')]);
            config(['broadcasting.connections.pusher.options.port' => setting('liveChatPort')]);
            config(['broadcasting.connections.pusher.options.host' => parse_url(url('/'))["host"]]);


        // Register plugins' registered translation folders
        foreach (Hook::execute('add_translation_file') as $source) {
            if (array_key_exists('translation_prefix', $source)) {
                $prefix = $source['translation_prefix'];
                $folder = $source['translation_folder'];
                $this->loadTranslationsFrom($folder, $prefix);
            }
        }

        Queue::before(function (JobProcessing $event) {
            // $event->connectionName
            // $event->job
            // $event->job->payload()
        });

        Queue::after(function (JobProcessed $event) {
            // $event->connectionName
            // $event->job
            // $event->job->payload()
        });

        Queue::failing(function (JobFailed $event) {
            // $event->connectionName
            // $event->job
            // $event->exception
        });
    }

    private function changeDefaultSettings()
    {
        ini_set('memory_limit', '-1');
        ini_set('pcre.backtrack_limit', 1000000000);

        // Laravel 5.5 to 5.6 compatibility
        Blade::withoutDoubleEncoding();

        // Check if HTTPS (including proxy case)
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') == 0) {
            $isSecure = true;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') == 0 || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_SSL'], 'on') == 0) {
            $isSecure = true;
        }

        if ($isSecure) {
            // To deal with Ajax pagination link issue (always use HTTP)
            $this->app['request']->server->set('HTTPS', 'on');
            URL::forceScheme('https');
        }

        // HTTP or HTTPS
        // parse_url will return either 'http' or 'https'
        //$scheme = parse_url(config('app.url'), PHP_URL_SCHEME);
        //if (!empty($scheme)) {
        //    AppUrl::forceScheme($scheme);
        //}

        // Fix Laravel 5.4 error
        // [Illuminate\Database\QueryException]
        // SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes
        Schema::defaultStringLength(191);
    }

}
