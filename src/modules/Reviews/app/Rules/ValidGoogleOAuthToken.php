<?php

namespace Modules\Reviews\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Validates Google OAuth token format.
 *
 * This rule ensures OAuth tokens meet basic format requirements:
 * - Not empty
 * - Minimum length (typically 32+ characters)
 * - Contains only valid characters (alphanumeric, dots, hyphens, underscores)
 *
 * Note: This does NOT validate token authenticity (use Google API for that).
 */
class ValidGoogleOAuthToken implements Rule
{
    private const MIN_TOKEN_LENGTH = 20;

    private const TOKEN_PATTERN = '/^[a-zA-Z0-9._\-\/]+$/';

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute name
     * @param  mixed  $value  The token value
     */
    public function passes($attribute, $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        // Check minimum length (Google tokens are typically 20+ chars)
        if (mb_strlen($value) < self::MIN_TOKEN_LENGTH) {
            return false;
        }

        // Check valid characters (alphanumeric, dots, hyphens, underscores, slashes)
        if (! preg_match(self::TOKEN_PATTERN, $value)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'El token de OAuth no tiene un formato válido';
    }
}
