<?php

namespace Modules\CampaignSendingServers\Library\Exception;

use Exception;

class RateLimitExceeded extends Exception
{
    protected ?int $retryAfter = null;

    public function __construct(string $message = '', int $retryAfter = 60, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter ?? 60;
    }
}
