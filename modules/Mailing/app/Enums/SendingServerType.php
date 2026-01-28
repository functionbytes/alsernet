<?php

namespace Modules\Mailing\Enums;

/**
 * Sending Server Type Enum
 *
 * Represents different types of mail sending services supported.
 */
enum SendingServerType: string
{
    case Smtp = 'smtp';
    case Sendgrid = 'sendgrid';
    case Mailgun = 'mailgun';
    case Ses = 'ses';
    case Postmark = 'postmark';
    case Sparkpost = 'sparkpost';
    case Mailjet = 'mailjet';

    /**
     * Get the human-readable label for the server type
     */
    public function label(): string
    {
        return match ($this) {
            self::Smtp => 'SMTP',
            self::Sendgrid => 'SendGrid',
            self::Mailgun => 'Mailgun',
            self::Ses => 'Amazon SES',
            self::Postmark => 'Postmark',
            self::Sparkpost => 'SparkPost',
            self::Mailjet => 'Mailjet',
        };
    }

    /**
     * Check if this is a third-party provider type
     */
    public function isThirdPartyProvider(): bool
    {
        return in_array($this, [
            self::Sendgrid,
            self::Mailgun,
            self::Ses,
            self::Postmark,
            self::Sparkpost,
            self::Mailjet,
        ]);
    }

    /**
     * Check if this is SMTP (self-hosted)
     */
    public function isSmtp(): bool
    {
        return $this === self::Smtp;
    }
}
