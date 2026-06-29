<?php

namespace Modules\Mailrelay\Services\EmailValidation\Validators;

use Modules\Mailrelay\Services\EmailValidation\ValidatorInterface;

class SyntaxValidator implements ValidatorInterface
{
    public function validate(string $email): array
    {
        // Validación RFC 5322
        $isValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        // Validaciones adicionales
        $details = [
            'has_at_symbol' => str_contains($email, '@'),
            'has_dot_in_domain' => $this->hasDotInDomain($email),
            'no_spaces' => ! str_contains($email, ' '),
            'valid_characters' => $this->hasValidCharacters($email),
        ];

        $score = $isValid ? 100 : 0;

        return [
            'valid' => $isValid,
            'score' => $score,
            'details' => $details,
            'message' => $isValid ? 'Valid email format' : 'Invalid email format',
        ];
    }

    public function getName(): string
    {
        return 'syntax';
    }

    private function hasDotInDomain(string $email): bool
    {
        if (! str_contains($email, '@')) {
            return false;
        }

        $parts = explode('@', $email);
        $domain = end($parts);

        return str_contains($domain, '.');
    }

    private function hasValidCharacters(string $email): bool
    {
        // Caracteres permitidos según RFC 5322
        $pattern = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/';

        return preg_match($pattern, $email) === 1;
    }
}
