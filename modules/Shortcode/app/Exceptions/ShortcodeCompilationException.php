<?php

namespace Modules\Shortcode\Exceptions;

use RuntimeException;
use Throwable;

class ShortcodeCompilationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $shortcodeName,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
