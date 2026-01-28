<?php

namespace Modules\Mailing\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class MailingException extends Exception
{
    public static function apiError(Response $response): self
    {
        $statusCode = $response->status();
        $body = $response->body();

        return new self(
            "Mailrelay API error (HTTP {$statusCode}): {$body}"
        );
    }

    public static function syncFailed(string $reason): self
    {
        return new self("Failed to sync with Mailrelay: {$reason}");
    }

    public static function connectionFailed(string $message = ''): self
    {
        return new self("Failed to connect to Mailrelay API: {$message}");
    }

    public static function subscriberNotFound(int $subscriberId): self
    {
        return new self("Subscriber not found: {$subscriberId}");
    }

    public static function campaignFailed(string $reason): self
    {
        return new self("Campaign operation failed: {$reason}");
    }

    public static function invalidApiKey(): self
    {
        return new self('Invalid Mailrelay API key');
    }
}
