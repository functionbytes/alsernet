<?php

namespace Modules\Mailing\Providers;

use Modules\Mailing\Library\Storage\S3;
use Modules\Mailing\Models\Setting;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // service business is implemented here, not recommended
        $this->app->bind('xstore', function ($app) {
            // Sample of valid setting: "s3:://apikey:secret@region:bucket"
            try {
                [$apiKey, $secret, $region, $bucket] = array_values(array_filter(preg_split('/(s3::\/\/)|([:@])/', Setting::get('storage.s3'))));

                $service = new S3($apiKey, $secret, $region, $bucket);

                return $service;
            } catch (\Exception $ex) {
                return null;
            }
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['xstore'];
    }
}
