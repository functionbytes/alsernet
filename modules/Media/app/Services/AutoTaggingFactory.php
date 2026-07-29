<?php

namespace Modules\Media\Services;

use Modules\Media\Contracts\AutoTaggingDriver;
use Modules\Media\Drivers\AutoTagging\ClaudeDriver;
use Modules\Media\Drivers\AutoTagging\GoogleVisionDriver;
use Modules\Media\Drivers\AutoTagging\NullDriver;
use Modules\Media\Drivers\AutoTagging\OpenAIDriver;

class AutoTaggingFactory
{
    public static function make(): AutoTaggingDriver
    {
        $driver = config('media.ai.auto_tagging.driver', 'null');
        $apiKey = (string) config('media.ai.auto_tagging.api_key');

        return match ($driver) {
            'claude' => new ClaudeDriver($apiKey),
            'openai' => new OpenAIDriver($apiKey),
            'google' => new GoogleVisionDriver($apiKey),
            default => new NullDriver,
        };
    }
}
