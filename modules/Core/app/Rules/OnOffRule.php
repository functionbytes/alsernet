<?php

namespace Modules\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OnOffRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, [0, 1, '0', '1', true, false, 'on', 'off', null], strict: true)) {
            $fail('El campo :attribute debe ser un valor de activación válido (0 o 1).');
        }
    }
}
