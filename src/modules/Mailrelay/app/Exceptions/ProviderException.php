<?php

namespace Modules\Mailrelay\Exceptions;

class ProviderException extends MailrelayException
{
    public static function providerConnectionFailed(string $provider, string $reason = ''): self
    {
        $message = "Provider '{$provider}' connection failed";

        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self($message);
    }

    public static function sendingFailed(string $to, string $reason): self
    {
        return new self("Failed to send email to {$to}: {$reason}");
    }

    public static function notConfigured(): self
    {
        return new self('No default mail provider is configured');
    }

    public static function invalidRecipient(string $email): self
    {
        return new self("Invalid recipient email address: {$email}");
    }

    public static function recipientRequired(): self
    {
        return new self('Recipient email address is required');
    }

    /**
     * @param  array<string, string>  $errors  Provider name => error message
     */
    public static function allProvidersFailed(array $errors): self
    {
        $details = collect($errors)
            ->map(fn (string $error, string $provider) => "{$provider}: {$error}")
            ->implode('; ');

        return new self("All providers failed. Details: {$details}");
    }
}
