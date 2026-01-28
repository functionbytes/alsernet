<?php

namespace Modules\Mailing\Enums;

/**
 * Bounce Handler Type Enum
 *
 * Represents different types of bounce handling mechanisms.
 */
enum BounceHandlerType: string
{
    case Imap = 'imap';
    case Pop3 = 'pop3';
    case Webhook = 'webhook';

    /**
     * Get the human-readable label for the handler type
     */
    public function label(): string
    {
        return match ($this) {
            self::Imap => 'IMAP',
            self::Pop3 => 'POP3',
            self::Webhook => 'Webhook',
        };
    }

    /**
     * Check if this is an email protocol type (IMAP or POP3)
     */
    public function isEmailProtocol(): bool
    {
        return in_array($this, [self::Imap, self::Pop3]);
    }

    /**
     * Check if this is a webhook type
     */
    public function isWebhook(): bool
    {
        return $this === self::Webhook;
    }
}
