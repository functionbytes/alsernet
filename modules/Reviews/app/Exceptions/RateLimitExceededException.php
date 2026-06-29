<?php

namespace Modules\Reviews\Exceptions;

use RuntimeException;

class RateLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly int $connectionId,
        public readonly string $limitType,
        public readonly int $retryAfterSeconds = 60,
    ) {
        parent::__construct("Rate limit exceeded for connection {$connectionId}: {$limitType}");
    }
}
