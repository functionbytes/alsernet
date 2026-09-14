<?php

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('La contraseña debe ser una cadena de texto.');

            return;
        }

        $policy = config('auth.auth-policy.password');
        $min = (int) ($policy['min_length'] ?? 10);

        if (mb_strlen($value) < $min) {
            $fail("La contraseña debe tener al menos {$min} caracteres.");

            return;
        }

        if (! empty($policy['require_uppercase']) && ! preg_match('/[A-Z]/', $value)) {
            $fail('La contraseña debe contener al menos una letra mayúscula.');

            return;
        }

        if (! empty($policy['require_lowercase']) && ! preg_match('/[a-z]/', $value)) {
            $fail('La contraseña debe contener al menos una letra minúscula.');

            return;
        }

        if (! empty($policy['require_numbers']) && ! preg_match('/[0-9]/', $value)) {
            $fail('La contraseña debe contener al menos un número.');

            return;
        }

        if (! empty($policy['require_symbols']) && ! preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('La contraseña debe contener al menos un símbolo.');
        }
    }
}
