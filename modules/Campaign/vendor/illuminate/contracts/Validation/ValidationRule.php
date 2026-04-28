<?php

namespace Illuminate\Contracts\Validation;

use Closure;
use Illuminate\Translation\PotentiallyTranslatedString;

interface ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void;
}
