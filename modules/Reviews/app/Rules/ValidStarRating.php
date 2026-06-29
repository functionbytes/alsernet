<?php

namespace Modules\Reviews\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\Reviews\Enums\ReviewRating;

/**
 * Validates that a value is a valid ReviewRating enum value.
 *
 * Accepts: 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE'
 * This rule ensures consistent star rating values across the application.
 */
class ValidStarRating implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute name
     * @param  mixed  $value  The value to validate
     */
    public function passes($attribute, $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        // Check if value matches any ReviewRating enum case
        return ReviewRating::tryFrom($value) !== null;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        $validValues = implode(', ', array_column(ReviewRating::cases(), 'value'));

        return "La calificación debe ser uno de los siguientes valores: {$validValues}";
    }
}
