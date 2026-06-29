<?php

namespace Modules\Reviews\Exceptions;

use Modules\Reviews\Models\ReviewGoogleConnection;
use RuntimeException;

class ConnectionExpiredException extends RuntimeException
{
    public function __construct(
        public readonly ReviewGoogleConnection $connection,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message ?: "Google connection #{$connection->id} has expired and has no valid refresh token.",
            $code,
            $previous
        );
    }
}
