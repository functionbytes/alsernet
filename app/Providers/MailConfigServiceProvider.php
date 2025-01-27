<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Config;
use DB;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // emailtemplatesetting();
        try {
            DB::connection()->getPdo();
            if (!DB::getSchemaBuilder()->hasTable('settings')) {
                return;
            } else {
                $config = array(
                    'driver'     => @('smtp'),
                    'host'       => @(DB::table('settings')->where('key', 'mail_host')->first()->value),
                    'port'       => @(DB::table('settings')->where('key', 'mail_port')->first()->value),
                    'from'       => @array('address' => @(DB::table('settings')->where('key', 'mail_from_address')->first()->value), 'name' => @(DB::table('settings')->where('key', 'mail_from_name')->first()->value)),
                    'encryption' => @(DB::table('settings')->where('key', 'mail_encryption')->first()->value),
                    'username'   => @(DB::table('settings')->where('key', 'mail_username')->first()->value),
                    'password'   => @(DB::table('settings')->where('key', 'mail_password')->first()->value),
                    'sendmail'   => @'/usr/sbin/sendmail -bs',
                    'pretend'    => @false,
                );
                Config::set('mail', $config);
            }
        } catch (\Exception $e) {
            return;
            die('Could not connect to the database.  Please check your configuration. error:' . $e);
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
