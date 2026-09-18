<?php

namespace Modules\Media\Services;

use Modules\Media\Contracts\PiiDetectionDriver;
use Modules\Media\Drivers\PiiDetection\ClaudeDriver;
use Modules\Media\Drivers\PiiDetection\NullDriver;

class PiiDetectionFactory
{
    public static function make(): PiiDetectionDriver
    {
        $driver = config('media.ai.pii_detection.driver', 'null');
        $apiKey = (string) config('media.ai.pii_detection.api_key');

        return match ($driver) {
            'claude' => new ClaudeDriver($apiKey),
            default => new NullDriver,
        };
    }
}
