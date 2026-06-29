<?php

namespace Modules\Reviews\Exceptions;

use Modules\Reviews\Models\ReviewReply;

class ReplyTooLongException extends \RuntimeException
{
    public function __construct(
        public readonly int $actualLength,
        public readonly int $maxLength = ReviewReply::GOOGLE_MAX_CHARS,
    ) {
        parent::__construct("Reply exceeds Google limit: {$actualLength}/{$maxLength} characters");
    }
}
