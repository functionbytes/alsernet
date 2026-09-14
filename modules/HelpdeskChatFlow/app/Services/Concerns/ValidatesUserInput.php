<?php

namespace Modules\HelpdeskChatFlow\Services\Concerns;

/**
 * Shared conversational-robustness helpers: input validation rules and the
 * "escape to a human agent" keyword detection. Used by both the live engine
 * and the test simulator so they behave identically.
 */
trait ValidatesUserInput
{
    /** @var array<int, string> */
    private array $defaultEscapeKeywords = ['agente', 'humano', 'persona real', 'operador', 'hablar con alguien'];

    /**
     * Whether a collected input passes the node's validation rule.
     */
    protected function passesValidation(string $rule, string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        return match ($rule) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'phone' => preg_match('/^[+\d][\d\s().-]{6,}$/', $value) === 1,
            'number' => is_numeric($value),
            default => true,
        };
    }

    /**
     * Short error shown when validation fails, by rule.
     */
    protected function validationError(string $rule): string
    {
        return match ($rule) {
            'email' => 'Eso no parece un email válido. ¿Puedes escribirlo de nuevo?',
            'phone' => 'Eso no parece un teléfono válido. ¿Puedes escribirlo de nuevo?',
            'number' => 'Necesito un número. ¿Puedes intentarlo de nuevo?',
            default => 'No he podido validar ese dato. ¿Puedes intentarlo de nuevo?',
        };
    }

    /**
     * Whether the customer is explicitly asking for a human agent.
     *
     * @param  array<int, string>|null  $keywords  Flow-level override
     */
    protected function isHumanEscapeRequest(string $message, ?array $keywords = null): bool
    {
        $keywords = $keywords ?: $this->defaultEscapeKeywords;
        $normalized = mb_strtolower(trim($message));

        if ($normalized === '') {
            return false;
        }

        foreach ($keywords as $keyword) {
            if (str_contains($normalized, mb_strtolower(trim($keyword)))) {
                return true;
            }
        }

        return false;
    }
}
