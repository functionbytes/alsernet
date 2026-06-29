<?php

namespace Modules\Shortcode\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShortcodeCompiling
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $content,
    ) {}
}
