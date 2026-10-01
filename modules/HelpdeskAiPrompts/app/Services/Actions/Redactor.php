<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

/**
 * Oculta datos personales (emails, teléfonos, IBAN, tarjetas) en lo que se
 * devuelve a la IA y en los registros de ejecución.
 */
class Redactor
{
    private const SENSITIVE_KEY = '/(e-?mail|phone|telefono|tel_|mobile|movil|iban|card|tarjeta|password|token|secret|authorization)/i';

    public function redactString(string $value): string
    {
        $value = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', '[email]', $value) ?? $value;
        $value = preg_replace('/\b[A-Z]{2}\d{2}(?:\s?[A-Z0-9]{4}){2,7}(?:\s?[A-Z0-9]{1,4})?\b/', '[iban]', $value) ?? $value;
        $value = preg_replace_callback('/(?<![\w])(?:\d[ \-]?){13,19}(?![\w])/', function (array $m): string {
            return $this->passesLuhn($m[0]) ? '[tarjeta]' : $m[0];
        }, $value) ?? $value;
        $value = preg_replace('/(?<![\w])(?:\+|00)\d{1,3}[ .\-]?\d(?:[ .\-]?\d){7,11}(?!\d)/', '[teléfono]', $value) ?? $value;

        return preg_replace('/(?<![\d.\-])[6-9]\d{2}[ .\-]?\d{3}[ .\-]?\d{3}(?!\d)/', '[teléfono]', $value) ?? $value;
    }

    /**
     * @param  array<int, string>  $allowedPaths  Rutas (con * por índice) que se dejan en claro
     */
    public function redactValue(mixed $value, string $path = '', array $allowedPaths = []): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $segment = is_int($key) ? '*' : (string) $key;
                $childPath = $path === '' ? $segment : $path.'.'.$segment;
                $out[$key] = $this->redactValue($item, $childPath, $allowedPaths);
            }

            return $out;
        }

        if (! is_string($value) || in_array($path, $allowedPaths, true)) {
            return $value;
        }

        $last = (string) (strrchr('.'.$path, '.') ?: '');
        if ($value !== '' && preg_match(self::SENSITIVE_KEY, ltrim($last, '.')) === 1) {
            return '[oculto]';
        }

        return $this->redactString($value);
    }

    /**
     * Resumen de argumentos para el registro de ejecuciones: nunca valores en
     * claro de emails/teléfonos; textos libres recortados.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function summarizeArgs(array $args): array
    {
        $summary = [];

        foreach ($args as $name => $value) {
            if (is_bool($value) || is_int($value) || is_float($value)) {
                $summary[$name] = $value;

                continue;
            }

            if (is_array($value)) {
                $summary[$name] = '[array:'.count($value).']';

                continue;
            }

            $text = (string) $value;
            if ($name === ActionParameters::EMAIL || preg_match(self::SENSITIVE_KEY, (string) $name) === 1) {
                $summary[$name] = '[oculto]';

                continue;
            }

            $summary[$name] = mb_substr($this->redactString($text), 0, 60);
        }

        return $summary;
    }

    private function passesLuhn(string $candidate): bool
    {
        $digits = preg_replace('/\D/', '', $candidate) ?? '';
        $length = strlen($digits);
        if ($length < 13 || $length > 19) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $digits[$length - 1 - $i];
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
