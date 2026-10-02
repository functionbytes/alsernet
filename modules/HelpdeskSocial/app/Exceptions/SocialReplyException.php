<?php

namespace Modules\HelpdeskSocial\Exceptions;

use RuntimeException;

class SocialReplyException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}
