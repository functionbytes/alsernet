<?php

namespace Modules\Shortcode\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShortcodeCompiled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $original,
        public readonly string $compiled,
        public readonly int $passes,
        public readonly bool $fromCache,
    ) {}
}
