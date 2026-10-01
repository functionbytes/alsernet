<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

use Modules\Helpdesk\Services\AI\PromptSanitizer;

/**
 * Convierte la respuesta cruda del bridge/HTTP en el texto que ve la IA: solo
 * los campos declarados en response.fields (o una lista blanca compacta),
 * datos personales ocultos, texto saneado y recortado a max_chars.
 */
class ResponseShaper
{
    private const MAX_LIST_ITEMS = 10;

    public function __construct(private readonly Redactor $redactor) {}

    /**
     * @param  array<string, mixed>  $response  response.fields / max_chars / empty_message / allow_pii
     */
    public function shape(mixed $data, array $response): string
    {
        $maxChars = $this->maxChars($response);
        $emptyMessage = (string) ($response['empty_message'] ?? '') ?: 'No hay resultados para esa consulta.';

        if (is_string($data)) {
            $data = ['text' => $data];
        }

        if (! is_array($data) || $data === []) {
            return $emptyMessage;
        }

        $fields = array_values(array_filter((array) ($response['fields'] ?? []), 'is_string'));
        $picked = $fields === [] ? $this->whitelist($data) : $this->pick($data, $fields);

        if ($this->isEmpty($picked)) {
            return $emptyMessage;
        }

        $allowed = array_values(array_filter((array) ($response['allow_pii'] ?? []), 'is_string'));
        $safe = $this->sanitizeStrings($this->redactor->redactValue($picked, '', $allowed));

        $json = (string) json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        return mb_strlen($json) > $maxChars ? mb_substr($json, 0, $maxChars).'…' : $json;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function maxChars(array $response): int
    {
        $max = (int) ($response['max_chars'] ?? config('ai-actions.default_max_chars', 1500));

        return min(max($max, 100), 6000);
    }

    /**
     * @param  array<int, string>  $fields  Rutas con puntos; "*" recorre listas
     */
    private function pick(array $data, array $fields): mixed
    {
        $result = [];

        foreach ($fields as $field) {
            $subset = $this->extract($data, explode('.', $field));
            if ($subset !== null) {
                $result = array_replace_recursive($result, $subset);
            }
        }

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @param  array<int, string>  $segments
     * @return array<int|string, mixed>|null
     */
    private function extract(array $data, array $segments): ?array
    {
        $segment = array_shift($segments);

        if ($segment === '*') {
            $out = [];
            foreach ($data as $index => $item) {
                $value = $this->descend($item, $segments);
                if ($value !== null) {
                    $out[$index] = $value;
                }
            }

            return $out === [] ? null : $out;
        }

        if (! array_key_exists($segment, $data)) {
            return null;
        }

        $value = $this->descend($data[$segment], $segments);

        return $value === null ? null : [$segment => $value];
    }

    /**
     * @param  array<int, string>  $segments
     */
    private function descend(mixed $value, array $segments): mixed
    {
        if ($segments === []) {
            return $value;
        }

        return is_array($value) ? $this->extract($value, $segments) : null;
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private function whitelist(array $data): array
    {
        $allowed = (array) config('ai-actions.default_response_keys', []);

        if (array_is_list($data)) {
            $items = [];
            foreach (array_slice($data, 0, self::MAX_LIST_ITEMS) as $item) {
                $items[] = is_array($item) ? $this->whitelist($item) : $item;
            }

            return array_values(array_filter($items, fn ($i) => ! $this->isEmpty($i)));
        }

        $out = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nested = $this->whitelist($value);
                if (! $this->isEmpty($nested)) {
                    $out[$key] = $nested;
                }

                continue;
            }

            if (in_array($key, $allowed, true) && $value !== null) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === [] || $value === '';
    }

    private function sanitizeStrings(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->sanitizeStrings($v), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $text = trim(strip_tags($value));

        return class_exists(PromptSanitizer::class) ? app(PromptSanitizer::class)->sanitize($text) : $text;
    }
}
