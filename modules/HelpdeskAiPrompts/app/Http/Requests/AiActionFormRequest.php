<?php

namespace Modules\HelpdeskAiPrompts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskAiPrompts\Models\AiAction;

/**
 * Forma y tipos de lo que envía el formulario de acciones. Que la definición
 * sea segura (lista blanca del bridge, hosts, reglas de escritura...) NO se
 * decide aquí sino en ActionDefinitionValidator, desde el evento `saving` del
 * modelo: su ValidationException llega igualmente como 422 por campo.
 */
abstract class AiActionFormRequest extends FormRequest
{
    private const JSON_FIELDS = ['payload', 'headers', 'body'];

    abstract protected function actionType(): string;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdesk.ai-prompts.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->actionType() === AiAction::TYPE_BUILTIN
            ? $this->builtinRules()
            : $this->customRules();
    }

    public function messages(): array
    {
        return [
            'config.payload.array' => __('helpdeskaiprompts::ai-prompts.actions.validation_json'),
            'config.headers.array' => __('helpdeskaiprompts::ai-prompts.actions.validation_json_object'),
            'config.body.array' => __('helpdeskaiprompts::ai-prompts.actions.validation_json'),
            'config.headers.*.string' => __('helpdeskaiprompts::ai-prompts.actions.validation_header_value'),
            'key.regex' => __('helpdeskaiprompts::ai-prompts.validation_key_regex'),
        ];
    }

    public function attributes(): array
    {
        return [
            'parameters.*.name' => __('helpdeskaiprompts::ai-prompts.actions.param_name'),
            'parameters.*.type' => __('helpdeskaiprompts::ai-prompts.actions.param_type'),
            'config.action' => __('helpdeskaiprompts::ai-prompts.actions.field_bridge_action'),
            'config.url' => __('helpdeskaiprompts::ai-prompts.actions.field_url'),
            'config.method' => __('helpdeskaiprompts::ai-prompts.actions.field_method'),
            'rules.ownership' => __('helpdeskaiprompts::ai-prompts.actions.field_ownership'),
            'rules.max_per_conversation' => __('helpdeskaiprompts::ai-prompts.actions.field_max_per_conversation'),
            'rules.timeout' => __('helpdeskaiprompts::ai-prompts.actions.field_timeout'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $config = (array) $this->input('config', []);

        foreach (self::JSON_FIELDS as $field) {
            if (array_key_exists($field, $config)) {
                $config[$field] = $this->decodeJson($config[$field]);
            }
        }

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'config' => $config,
            'channels' => array_values(array_filter((array) $this->input('channels', []))),
            'parameters' => array_map($this->normalizeParameter(...), array_values((array) $this->input('parameters', []))),
            'response' => $this->normalizeResponse((array) $this->input('response', [])),
            'rules' => $this->normalizeRules((array) $this->input('rules', [])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function builtinRules(): array
    {
        return [
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customRules(): array
    {
        $isBridge = $this->actionType() === AiAction::TYPE_BRIDGE;

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'channels' => ['array'],
            'channels.*' => [Rule::in(Inbox::CHANNEL_TYPES)],

            'parameters' => ['array', 'max:12'],
            'parameters.*.name' => ['required', 'string', 'max:32'],
            'parameters.*.type' => ['required', 'string', 'max:16'],
            'parameters.*.description' => ['nullable', 'string', 'max:300'],
            'parameters.*.required' => ['boolean'],
            'parameters.*.enum' => ['nullable', 'array'],
            'parameters.*.enum.*' => ['string', 'max:64'],
            'parameters.*.pattern' => ['nullable', 'string', 'max:200'],
            'parameters.*.max_length' => ['nullable', 'integer', 'between:1,2000'],

            'config' => ['array'],
            'config.action' => [$isBridge ? 'required' : 'prohibited', 'string', 'max:64'],
            'config.payload' => ['nullable', 'array'],
            'config.method' => [$isBridge ? 'prohibited' : 'required', 'string', Rule::in(['GET', 'POST', 'get', 'post'])],
            'config.url' => [$isBridge ? 'prohibited' : 'required', 'string', 'max:2048'],
            'config.headers' => ['nullable', 'array'],
            'config.headers.*' => ['string', 'max:500'],
            'config.body' => ['nullable', 'array'],

            'auth' => ['nullable', 'array'],
            'auth.type' => ['nullable', Rule::in(['none', 'bearer', 'header'])],
            'auth.header' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'auth.value' => ['nullable', 'string', 'max:2000'],

            'response' => ['array'],
            'response.fields' => ['array', 'max:60'],
            'response.fields.*' => ['string', 'max:150'],
            'response.allow_pii' => ['array', 'max:30'],
            'response.allow_pii.*' => ['string', 'max:150'],
            'response.max_chars' => ['nullable', 'integer'],
            'response.empty_message' => ['nullable', 'string', 'max:300'],

            'rules' => ['array'],
            'rules.ownership' => ['required', 'string', 'max:24'],
            'rules.requires_verified' => ['boolean'],
            'rules.confirm' => ['boolean'],
            'rules.max_per_conversation' => ['required', 'integer'],
            'rules.timeout' => ['required', 'integer'],
        ];
    }

    /**
     * Texto vacío = sin plantilla. Si no decodifica a array se deja tal cual
     * y la regla `array` lo rechaza con un mensaje claro.
     */
    private function decodeJson(mixed $raw): mixed
    {
        if (! is_string($raw)) {
            return $raw;
        }

        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : $raw;
    }

    /**
     * @param  array<string, mixed>  $param
     * @return array<string, mixed>
     */
    private function normalizeParameter(mixed $param): array
    {
        $param = (array) $param;
        $enum = array_values(array_filter(array_map('trim', explode(',', (string) ($param['enum_text'] ?? '')))));
        $maxLength = $param['max_length'] ?? '';
        $pattern = trim((string) ($param['pattern'] ?? ''));

        return array_filter([
            'name' => trim((string) ($param['name'] ?? '')),
            'type' => (string) ($param['type'] ?? 'string'),
            'description' => trim((string) ($param['description'] ?? '')),
            'required' => filter_var($param['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'enum' => ($param['type'] ?? '') === 'enum' ? $enum : null,
            'pattern' => $pattern !== '' ? $pattern : null,
            'max_length' => $maxLength !== '' && is_numeric($maxLength) ? (int) $maxLength : null,
        ], fn ($value, $key): bool => $key === 'required' || $key === 'name' || $key === 'type' || ($value !== null && $value !== ''), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function normalizeResponse(array $response): array
    {
        $clean = fn (mixed $list): array => array_values(array_filter(array_map(
            fn ($path): string => trim((string) $path),
            (array) $list,
        )));

        return [
            'fields' => $clean($response['fields'] ?? []),
            'allow_pii' => $clean($response['allow_pii'] ?? []),
            'max_chars' => ($response['max_chars'] ?? '') === '' ? null : $response['max_chars'],
            'empty_message' => $response['empty_message'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function normalizeRules(array $rules): array
    {
        return [
            'ownership' => $rules['ownership'] ?? 'none',
            'requires_verified' => filter_var($rules['requires_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'confirm' => filter_var($rules['confirm'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'max_per_conversation' => $rules['max_per_conversation'] ?? '',
            'timeout' => $rules['timeout'] ?? '',
        ];
    }
}
