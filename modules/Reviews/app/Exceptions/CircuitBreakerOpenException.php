<?php

namespace Modules\Reviews\Exceptions;

use RuntimeException;

class CircuitBreakerOpenException extends RuntimeException
{
    public function __construct(
        public readonly string $serviceKey,
        string $message = '',
    ) {
        parent::__construct($message ?: "Circuit breaker open for: {$serviceKey}");
    }
}
