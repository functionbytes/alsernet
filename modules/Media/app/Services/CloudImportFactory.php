<?php

namespace Modules\Media\Services;

use Modules\Media\Contracts\CloudImportDriver;
use Modules\Media\Drivers\Cloud\DropboxDriver;
use Modules\Media\Drivers\Cloud\GoogleDriveDriver;
use Modules\Media\Drivers\Cloud\OneDriveDriver;

class CloudImportFactory
{
    public static function make(string $provider): ?CloudImportDriver
    {
        $config = config('media.cloud_import.'.$provider);

        if (! $config || empty($config['client_id'])) {
            return null;
        }

        return match ($provider) {
            'dropbox' => new DropboxDriver($config['client_id'], $config['client_secret'], $config['redirect_uri']),
            'gdrive' => new GoogleDriveDriver($config['client_id'], $config['client_secret'], $config['redirect_uri']),
            'onedrive' => new OneDriveDriver($config['client_id'], $config['client_secret'], $config['redirect_uri']),
            default => null,
        };
    }
}
