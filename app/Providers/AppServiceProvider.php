<?php

namespace App\Providers;

use App\Models\Setting\Setting;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
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

        if(file_exists(storage_path('installed'))){
            config(['websockets.dashboard.port' => setting('liveChatPort')]);
            config(['broadcasting.connections.pusher.options.port' => setting('liveChatPort')]);
            config(['broadcasting.connections.pusher.options.host' => parse_url(url('/'))["host"]]);
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

}
