<?php

declare(strict_types=1);

namespace Modules\Engagement\Services;

use Illuminate\Support\Facades\Cache;

class ReverbSettingsService
{
    public static function getSettings(): array
    {
        return Cache::remember('engagement:reverb:settings', 60, function () {
            return [
                'enabled' => config('broadcasting.default') === 'reverb',
                'host' => config('reverb.servers.reverb.host', '0.0.0.0'),
                'port' => config('reverb.servers.reverb.port', 8080),
                'scheme' => config('reverb.servers.reverb.options.scheme', 'https'),
                'app_id' => config('reverb.apps.apps.0.app_id', ''),
                'key' => config('reverb.apps.apps.0.key', ''),
                'options' => [
                    'authEndpoint' => '/broadcasting/auth',
                ],
            ];
        });
    }

    public static function isEnabled(): bool
    {
        return self::getSettings()['enabled'];
    }
}
