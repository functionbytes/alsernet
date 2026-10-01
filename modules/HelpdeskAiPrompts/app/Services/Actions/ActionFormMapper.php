<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Support\ToolCatalog;

/**
 * Traduce entre el formulario del panel y la definición de una acción.
 * `attributes()` solo arma atributos del modelo; la validez de la definición
 * (lista blanca, hosts, reglas de escritura...) la decide siempre el
 * ActionDefinitionValidator desde el evento `saving` del modelo.
 *
 * Los secretos nunca salen de aquí hacia la vista: `toForm()` solo expone si
 * ya hay uno guardado.
 */
class ActionFormMapper
{
    public const SECRET_NAMES = ['bearer' => 'token', 'header' => 'api_key'];

    /**
     * @param  array<string, mixed>  $input  datos validados del formulario
     * @return array<string, mixed>
     */
    public function attributes(array $input, string $type, ?AiAction $existing = null): array
    {
        $attributes = [
            'name' => $input['name'],
            'description' => $input['description'],
            'type' => $type,
            'is_active' => (bool) ($input['is_active'] ?? false),
            'channels' => ($input['channels'] ?? []) ?: null,
            'parameters' => array_values((array) ($input['parameters'] ?? [])),
            'response' => $this->response((array) ($input['response'] ?? [])),
            'rules' => $this->rules((array) ($input['rules'] ?? [])),
            'updated_by' => auth()->id(),
        ];

        if (isset($input['key'])) {
            $attributes['key'] = $input['key'];
        }

        return $attributes + $this->typeAttributes($input, $type, $existing);
    }

    /**
     * Las integradas solo se activan/desactivan y redescriben. Descripción
     * vacía = vuelve a la del código.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function builtinAttributes(array $input, AiAction $action): array
    {
        $override = trim((string) ($input['description'] ?? ''));

        return [
            'is_active' => (bool) ($input['is_active'] ?? false),
            'description' => $override !== '' ? $override : $this->codeDescription($action),
            'config' => array_merge((array) $action->config, ['description_overridden' => $override !== '']),
            'updated_by' => auth()->id(),
        ];
    }

    public function codeDescription(AiAction $action): string
    {
        return ToolCatalog::TOOLS[$action->key] ?? (string) $action->description;
    }

    /**
     * Valores iniciales del formulario (nunca incluye secretos).
     *
     * @return array<string, mixed>
     */
    public function toForm(AiAction $action): array
    {
        $config = (array) $action->config;
        $rules = (array) $action->rules + [
            'ownership' => 'verified',
            'requires_verified' => true,
            'confirm' => false,
            'max_per_conversation' => (int) config('ai-actions.default_max_per_conversation', 5),
            'timeout' => (int) config('ai-actions.default_timeout', 8),
        ];
        $response = (array) $action->response;
        $auth = (array) ($config['auth'] ?? []);

        return [
            'is_builtin' => $action->type === AiAction::TYPE_BUILTIN,
            'description_overridden' => ! empty($config['description_overridden']),
            'code_description' => $action->type === AiAction::TYPE_BUILTIN ? $this->codeDescription($action) : null,
            'channels' => (array) $action->channels,
            'parameters' => array_map(fn (array $p): array => $p + ['enum_text' => implode(', ', (array) ($p['enum'] ?? []))], array_values((array) $action->parameters)),
            'bridge_action' => (string) ($config['action'] ?? ''),
            'payload' => $this->prettyJson($config['payload'] ?? []),
            'method' => (string) ($config['method'] ?? 'GET'),
            'url' => (string) ($config['url'] ?? ''),
            'headers' => $this->prettyJson($config['headers'] ?? []),
            'body' => $this->prettyJson($config['body'] ?? []),
            'auth_type' => (string) ($auth['type'] ?? 'none'),
            'auth_header' => (string) ($auth['header'] ?? 'X-Api-Key'),
            'has_secret' => ! empty($auth['secret']) && $this->hasSecret($action, (string) $auth['secret']),
            'response_fields' => (array) ($response['fields'] ?? []),
            'allow_pii' => (array) ($response['allow_pii'] ?? []),
            'max_chars' => $response['max_chars'] ?? '',
            'empty_message' => (string) ($response['empty_message'] ?? ''),
            'rules' => $rules,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function typeAttributes(array $input, string $type, ?AiAction $existing): array
    {
        $config = (array) ($input['config'] ?? []);

        if ($type === AiAction::TYPE_BRIDGE) {
            return [
                'config' => ['action' => (string) ($config['action'] ?? ''), 'payload' => (array) ($config['payload'] ?? [])],
                'secrets' => null,
            ];
        }

        $httpConfig = array_filter([
            'method' => strtoupper((string) ($config['method'] ?? 'GET')),
            'url' => (string) ($config['url'] ?? ''),
            'headers' => (array) ($config['headers'] ?? []),
            'body' => (array) ($config['body'] ?? []),
        ], fn ($value): bool => $value !== [] && $value !== '');

        $auth = $this->auth((array) ($input['auth'] ?? []), $existing);

        if ($auth['config'] !== null) {
            $httpConfig['auth'] = $auth['config'];
        }

        return ['config' => $httpConfig, 'secrets' => $auth['secrets']];
    }

    /**
     * Valor en blanco = conservar el secreto ya guardado con ese nombre.
     *
     * @param  array<string, mixed>  $auth
     * @return array{config: ?array<string, string>, secrets: ?array<string, string>}
     */
    private function auth(array $auth, ?AiAction $existing): array
    {
        $type = (string) ($auth['type'] ?? 'none');

        if (! isset(self::SECRET_NAMES[$type])) {
            return ['config' => null, 'secrets' => null];
        }

        $name = self::SECRET_NAMES[$type];
        $value = (string) ($auth['value'] ?? '');
        $kept = (string) (($existing?->secrets ?? [])[$name] ?? '');
        $secret = $value !== '' ? $value : $kept;

        $config = ['type' => $type, 'secret' => $name];
        if ($type === 'header') {
            $config['header'] = (string) ($auth['header'] ?? '') ?: 'X-Api-Key';
        }

        return ['config' => $config, 'secrets' => $secret !== '' ? [$name => $secret] : null];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function response(array $response): array
    {
        return array_filter([
            'fields' => array_values((array) ($response['fields'] ?? [])),
            'allow_pii' => array_values((array) ($response['allow_pii'] ?? [])),
            'max_chars' => isset($response['max_chars']) && $response['max_chars'] !== '' ? (int) $response['max_chars'] : null,
            'empty_message' => trim((string) ($response['empty_message'] ?? '')),
        ], fn ($value): bool => $value !== [] && $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function rules(array $rules): array
    {
        return [
            'ownership' => (string) ($rules['ownership'] ?? 'none'),
            'requires_verified' => (bool) ($rules['requires_verified'] ?? false),
            'confirm' => (bool) ($rules['confirm'] ?? false),
            'max_per_conversation' => (int) ($rules['max_per_conversation'] ?? config('ai-actions.default_max_per_conversation', 5)),
            'timeout' => (int) ($rules['timeout'] ?? config('ai-actions.default_timeout', 8)),
        ];
    }

    private function hasSecret(AiAction $action, string $name): bool
    {
        // `secrets` no viene en las consultas de listado: solo se mira en la edición.
        return ! empty(($action->secrets ?? [])[$name]);
    }

    private function prettyJson(mixed $value): string
    {
        return $value === [] || $value === null
            ? ''
            : (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
