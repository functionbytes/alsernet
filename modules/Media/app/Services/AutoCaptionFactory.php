<?php

namespace Modules\Media\Services;

use Modules\Media\Contracts\AutoCaptionDriver;
use Modules\Media\Drivers\AutoCaption\ClaudeDriver;
use Modules\Media\Drivers\AutoCaption\NullDriver;
use Modules\Media\Drivers\AutoCaption\OpenAIDriver;

class AutoCaptionFactory
{
    public static function make(): AutoCaptionDriver
    {
        $driver = config('media.ai.auto_caption.driver', 'null');
        $apiKey = (string) config('media.ai.auto_caption.api_key');

        return match ($driver) {
            'claude' => new ClaudeDriver($apiKey),
            'openai' => new OpenAIDriver($apiKey),
            default => new NullDriver,
        };
    }
}
