<?php

namespace Modules\Mailrelay\Exceptions;

use Exception;

class EmailValidationException extends Exception
{
    public static function invalidEmail(string $email): self
    {
        return new self("Email format is invalid: {$email}");
    }

    public static function invalidFormat(string $email): self
    {
        return new self("Email format is invalid: {$email}");
    }

    public static function providerFailed(string $provider): self
    {
        return new self("Validation provider failed: {$provider}");
    }

    public static function allProvidersFailed(): self
    {
        return new self('All validation providers failed');
    }
}
