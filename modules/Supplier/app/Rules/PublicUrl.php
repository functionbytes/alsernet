<?php

namespace Modules\Supplier\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Supplier\Traits\ValidatesPublicUrl;

/**
 * Rejects URLs that resolve to private, loopback, link-local or reserved
 * addresses to prevent SSRF when the URL is later requested by the server.
 */
class PublicUrl implements ValidationRule
{
    use ValidatesPublicUrl;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('El campo :attribute debe ser una URL válida.');

            return;
        }

        try {
            $this->assertUrlIsPublic($value);
        } catch (\InvalidArgumentException) {
            $fail('El campo :attribute debe apuntar a una dirección pública (no se permiten IPs internas).');
        }
    }
}
