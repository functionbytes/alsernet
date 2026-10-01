<?php

namespace Modules\HelpdeskChatFlow\Services\Concerns;

use Illuminate\Support\Str;
use Modules\HelpdeskChatFlow\Services\Support\ContextPath;
use Modules\HelpdeskChatFlow\Services\Support\SafeRegex;

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
     *
     * @param  array<string, mixed>  $data  Node data (`pattern` for regex, `allowed` for enum)
     */
    protected function passesValidation(string $rule, string $value, array $data = []): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        return match ($rule) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'phone' => preg_match('/^[+\d][\d\s().-]{6,}$/', $value) === 1,
            'number' => is_numeric($value),
            'order_ref' => $this->normalizeOrderRef($value) !== null,
            'regex' => SafeRegex::matches((string) ($data['pattern'] ?? ''), $value),
            'enum' => $this->matchAllowedOption($value, $data['allowed'] ?? []) !== null,
            default => true,
        };
    }

    /**
     * The value to store once it has passed validation: order references are
     * upper-cased and enum answers become the canonical option as written in
     * the node, so branches can compare against it exactly.
     *
     * @param  array<string, mixed>  $data
     */
    protected function normalizeInput(string $rule, string $value, array $data = []): string
    {
        return match ($rule) {
            'order_ref' => $this->normalizeOrderRef(trim($value)) ?? $value,
            'enum' => $this->matchAllowedOption(trim($value), $data['allowed'] ?? []) ?? $value,
            default => $value,
        };
    }

    /**
     * Short error shown when validation fails, by rule. A node may override it
     * with its own `error_message`.
     *
     * @param  array<string, mixed>  $data
     */
    protected function validationError(string $rule, array $data = []): string
    {
        $custom = trim((string) ($data['error_message'] ?? ''));

        if ($custom !== '') {
            return $custom;
        }

        return match ($rule) {
            'email' => 'Eso no parece un email válido. ¿Puedes escribirlo de nuevo?',
            'phone' => 'Eso no parece un teléfono válido. ¿Puedes escribirlo de nuevo?',
            'number' => 'Necesito un número. ¿Puedes intentarlo de nuevo?',
            'order_ref' => 'Eso no parece un número o referencia de pedido válidos. La referencia son 9 letras (por ejemplo ABCDEFGHI). ¿Puedes escribirlo de nuevo?',
            'regex' => 'Ese dato no tiene el formato esperado. ¿Puedes escribirlo de nuevo?',
            'enum' => $this->enumError($data['allowed'] ?? []),
            default => 'No he podido validar ese dato. ¿Puedes intentarlo de nuevo?',
        };
    }

    /**
     * collect_input with `skip_if_set`: the question is not asked when the
     * context already holds a value for its variable (e.g. customer_email of a
     * verified customer) and the flow moves on to the next node.
     *
     * @param  array<string, mixed>  $data  Node data
     * @param  array<string, mixed>  $context
     */
    protected function shouldSkipCollectInput(array $data, array $context): bool
    {
        if (! filter_var($data['skip_if_set'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $variable = (string) ($data['variable_name'] ?? '');

        return $variable !== '' && ContextPath::isFilled($context, $variable);
    }

    /**
     * Numeric id (1-10 digits, optional leading #) or PrestaShop reference
     * (9 letters, any case), returned in canonical form; null when neither.
     */
    private function normalizeOrderRef(string $value): ?string
    {
        $value = ltrim($value, '#');

        if (preg_match('/^\d{1,10}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^[A-Za-z]{9}$/', $value) === 1) {
            return strtoupper($value);
        }

        return null;
    }

    /**
     * The allowed option the value corresponds to, ignoring case and accents.
     *
     * @param  mixed  $allowed  List of options (any other shape yields no match)
     */
    private function matchAllowedOption(string $value, mixed $allowed): ?string
    {
        if (! is_array($allowed)) {
            return null;
        }

        $needle = $this->foldForComparison($value);

        foreach ($allowed as $option) {
            if (is_scalar($option) && $this->foldForComparison((string) $option) === $needle) {
                return trim((string) $option);
            }
        }

        return null;
    }

    private function foldForComparison(string $text): string
    {
        return Str::ascii(mb_strtolower(trim($text)));
    }

    private function enumError(mixed $allowed): string
    {
        $options = is_array($allowed)
            ? array_values(array_filter(array_map(fn ($o) => is_scalar($o) ? trim((string) $o) : '', $allowed)))
            : [];

        if ($options === []) {
            return 'No he podido validar ese dato. ¿Puedes intentarlo de nuevo?';
        }

        return 'Elige una de estas opciones: '.implode(', ', $options).'.';
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
