<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskAiPrompts\Models\AiAction;

/**
 * Qué herramientas ofrece el catálogo a la IA. Lee de caché (5 min, invalidada
 * al guardar desde el ServiceProvider); nunca carga ni cachea `secrets`.
 */
class ActionRegistry
{
    public const CACHE_TTL = 300;

    public const CACHE_KEY_ACTIVE = 'helpdesk_ai_actions:active';

    public const CACHE_KEY_BUILTINS = 'helpdesk_ai_actions:builtins';

    private const COLUMNS = ['key', 'name', 'description', 'type', 'is_active', 'parameters', 'config', 'rules', 'channels'];

    /**
     * Definiciones de herramienta (formato OpenAI) de las acciones bridge/http
     * activas y permitidas para el canal.
     *
     * @param  array<string, mixed>  $ctx  verified, channel...
     * @return array<int, array<string, mixed>>
     */
    public function toolsFor(array $ctx): array
    {
        $verified = ! empty($ctx['verified']);
        $channel = $ctx['channel'] ?? null;
        $tools = [];

        foreach ($this->active() as $action) {
            if (! $this->allowedForChannel($action['channels'] ?? null, $channel)) {
                continue;
            }

            $tools[] = $this->toTool($action, $verified);
        }

        return $tools;
    }

    /**
     * @return array<string, array{enabled: bool, description: ?string}>
     */
    public function builtinOverrides(): array
    {
        $rows = Cache::remember(self::CACHE_KEY_BUILTINS, self::CACHE_TTL, fn (): array => AiAction::query()
            ->where('type', AiAction::TYPE_BUILTIN)
            ->get(['key', 'description', 'is_active', 'config'])
            ->map(fn (AiAction $a): array => [
                'key' => $a->key,
                'enabled' => (bool) $a->is_active,
                'description' => ! empty($a->config['description_overridden']) && trim((string) $a->description) !== ''
                    ? (string) $a->description
                    : null,
            ])
            ->all());

        $overrides = [];
        foreach ($rows as $row) {
            $overrides[$row['key']] = ['enabled' => $row['enabled'], 'description' => $row['description']];
        }

        return $overrides;
    }

    public function isCustom(string $key): bool
    {
        foreach ($this->active() as $action) {
            if ($action['key'] === $key) {
                return true;
            }
        }

        return false;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY_ACTIVE);
        Cache::forget(self::CACHE_KEY_BUILTINS);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function active(): array
    {
        return Cache::remember(self::CACHE_KEY_ACTIVE, self::CACHE_TTL, fn (): array => AiAction::query()
            ->where('is_active', true)
            ->whereIn('type', [AiAction::TYPE_BRIDGE, AiAction::TYPE_HTTP])
            ->orderBy('key')
            ->get(self::COLUMNS)
            ->map(fn (AiAction $a): array => $a->only(self::COLUMNS))
            ->all());
    }

    /**
     * @param  array<int, string>|null  $channels
     */
    private function allowedForChannel(?array $channels, ?string $channel): bool
    {
        if ($channels === null || $channels === []) {
            return true;
        }

        return $channel !== null && in_array($channel, $channels, true);
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>
     */
    private function toTool(array $action, bool $verified): array
    {
        $properties = [];
        $required = [];

        foreach (ActionParameters::effective($action, $verified) as $param) {
            $properties[$param['name']] = $this->toSchema($param);
            if (! empty($param['required'])) {
                $required[] = $param['name'];
            }
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $action['key'],
                'description' => (string) $action['description'],
                'parameters' => [
                    'type' => 'object',
                    // Sin parámetros: {} y no [] — OpenAI rechaza la petición entera si es una lista.
                    'properties' => $properties === [] ? new \stdClass : $properties,
                    'required' => $required,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $param
     * @return array<string, mixed>
     */
    private function toSchema(array $param): array
    {
        $schema = match ($param['type']) {
            'integer' => ['type' => 'integer'],
            'number' => ['type' => 'number'],
            'boolean' => ['type' => 'boolean'],
            'email' => ['type' => 'string', 'format' => 'email'],
            'enum' => ['type' => 'string', 'enum' => array_values((array) ($param['enum'] ?? []))],
            default => ['type' => 'string'],
        };

        if (isset($param['max_length']) && $param['type'] === 'string') {
            $schema['maxLength'] = (int) $param['max_length'];
        }

        $schema['description'] = (string) ($param['description'] ?? '');

        return $schema;
    }
}
