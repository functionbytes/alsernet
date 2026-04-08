<?php

namespace Modules\Reviews\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Validates that a value is a valid JSON array with optional constraints.
 *
 * This rule ensures JSON data is properly structured as an array and can
 * enforce limits on array size and string length of items.
 */
class ValidJsonArray implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @param  int|null  $maxItems  Maximum number of items allowed in array
     * @param  int|null  $maxStringLength  Maximum length of each string item
     */
    public function __construct(
        private readonly ?int $maxItems = null,
        private readonly ?int $maxStringLength = null
    ) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute name
     * @param  mixed  $value  The value to validate
     */
    public function passes($attribute, $value): bool
    {
        // Must be an array
        if (! is_array($value)) {
            return false;
        }

        // Check max items constraint
        if ($this->maxItems !== null && count($value) > $this->maxItems) {
            return false;
        }

        // Check max string length constraint for each item
        if ($this->maxStringLength !== null) {
            foreach ($value as $item) {
                if (is_string($item) && mb_strlen($item) > $this->maxStringLength) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        if ($this->maxItems !== null && $this->maxStringLength !== null) {
            return "El campo debe ser un array válido con máximo {$this->maxItems} elementos y cada texto no debe exceder {$this->maxStringLength} caracteres";
        }

        if ($this->maxItems !== null) {
            return "El campo debe ser un array válido con máximo {$this->maxItems} elementos";
        }

        if ($this->maxStringLength !== null) {
            return "El campo debe ser un array válido y cada texto no debe exceder {$this->maxStringLength} caracteres";
        }

        return 'El campo debe ser un array válido';
    }
}
