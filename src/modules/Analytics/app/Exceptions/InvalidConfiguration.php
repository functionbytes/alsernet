<?php

namespace Modules\Analytics\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    public static function credentialsIsNotValid(): self
    {
        return new self('Google Analytics credentials are not valid. Please check the analytics settings and ensure valid credentials are configured.');
    }

    public static function invalidPropertyId(): self
    {
        return new self('Google Analytics property ID is invalid. Please provide a valid numeric property ID.');
    }
}
