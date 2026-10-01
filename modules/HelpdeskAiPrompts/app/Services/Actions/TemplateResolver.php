<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

/**
 * Resuelve plantillas con {{args.x}}, {{customer.email}}, {{customer.ps_id}},
 * {{order.id}} y {{conversation.id}}. Admite valor por defecto: {{args.x|0}}.
 *
 * Variables: ['args' => [...], 'customer' => ?array, 'order' => ?array,
 * 'conversation' => ['id' => ?int]]. `customer` es null mientras el cliente no
 * esté verificado: ahí customer.* NO se resuelve (corta con ActionRefusal),
 * nunca se rellena con lo que diga el modelo.
 */
class TemplateResolver
{
    private const PLACEHOLDER = '/\{\{\s*([a-z_]+(?:\.[a-z_]+)?)\s*(?:\|([^}]*))?\}\}/';

    private const WHOLE = '/^\{\{\s*([a-z_]+(?:\.[a-z_]+)?)\s*(?:\|([^}]*))?\}\}$/';

    /**
     * @param  array<string, mixed>  $vars
     */
    public function resolve(mixed $template, array $vars, bool $urlEncode = false): mixed
    {
        if (is_array($template)) {
            $out = [];
            foreach ($template as $key => $item) {
                $resolved = $this->resolve($item, $vars, $urlEncode);
                if ($resolved === null && is_string($item) && preg_match(self::WHOLE, $item) === 1) {
                    continue; // placeholder opcional sin valor: la clave se omite
                }
                $out[$key] = $resolved;
            }

            return $out;
        }

        if (! is_string($template)) {
            return $template;
        }

        if (preg_match(self::WHOLE, $template, $m) === 1) {
            return $this->lookup($m[1], isset($m[2]) ? trim($m[2]) : null, $vars, true);
        }

        return preg_replace_callback(self::PLACEHOLDER, function (array $m) use ($vars, $urlEncode): string {
            $value = $this->lookup($m[1], isset($m[2]) ? trim($m[2]) : null, $vars, false);
            $text = is_array($value) ? (string) json_encode($value) : (string) ($value ?? '');

            return $urlEncode ? rawurlencode($text) : $text;
        }, $template) ?? $template;
    }

    /**
     * @return array<int, string>
     */
    public function variablesIn(array $template): array
    {
        $found = [];
        array_walk_recursive($template, function ($item) use (&$found): void {
            if (is_string($item) && preg_match_all(self::PLACEHOLDER, $item, $m) > 0) {
                array_push($found, ...$m[1]);
            }
        });

        return array_values(array_unique($found));
    }

    /**
     * @param  array<string, mixed>  $vars
     */
    private function lookup(string $path, ?string $default, array $vars, bool $typed): mixed
    {
        [$root, $leaf] = explode('.', $path.'.', 3);

        if ($root === 'customer' && ($vars['customer'] ?? null) === null) {
            throw ActionRefusal::denied('Necesito identificar al cliente antes de hacer eso.', 'customer_variable_unverified');
        }

        if ($root === 'order' && ($vars['order'] ?? null) === null) {
            throw ActionRefusal::denied('No he podido localizar el pedido.', 'order_variable_unresolved');
        }

        $value = $vars[$root][$leaf] ?? null;

        if ($value === null && $default !== null) {
            return $typed && is_numeric($default) ? $default + 0 : $default;
        }

        return $value;
    }
}
