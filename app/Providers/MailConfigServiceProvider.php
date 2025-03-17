<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MailConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        try {

            if (!DB::connection()->getPdo()) {
                return;
            }

            if (!DB::getSchemaBuilder()->hasTable('settings')) {
                return;
            }

            $settings = Cache::remember('mail_settings', now()->addMinutes(10), function () {
                return DB::table('settings')
                    ->whereIn('key', [
                        'mail_host', 'mail_port', 'mail_from_address', 'mail_from_name',
                        'mail_encryption', 'mail_username', 'mail_password'
                    ])
                    ->pluck('value', 'key');
            });

            Config::set('mail', [
                'driver'     => 'smtp',
                'host'       => $settings['mail_host'] ?? 'smtp.example.com',
                'port'       => $settings['mail_port'] ?? 587,
                'from'       => [
                    'address' => $settings['mail_from_address'] ?? 'noreply@example.com',
                    'name'    => $settings['mail_from_name'] ?? 'Example App',
                ],
                'encryption' => $settings['mail_encryption'] ?? 'tls',
                'username'   => $settings['mail_username'] ?? null,
                'password'   => $settings['mail_password'] ?? null,
                'sendmail'   => '/usr/sbin/sendmail -bs',
                'pretend'    => false,
            ]);

        } catch (\Exception $e) {
            \Log::error('MailConfigServiceProvider Error: ' . $e->getMessage());
        }

    }

    public function boot(): void
    {

    }

}
