<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAiAgentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.aiagents.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'provider' => ['required', 'in:openai,anthropic,gemini,local'],
            'model' => ['required', 'string', 'max:255'],
            'personality' => ['required', 'string', 'max:10000'],
            'status' => ['required', 'in:inactive,active,paused'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:128000'],
            'top_p' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'frequency_penalty' => ['nullable', 'numeric', 'min:-2', 'max:2'],
            'presence_penalty' => ['nullable', 'numeric', 'min:-2', 'max:2'],
            'organization_id' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:255'],
            'base_url' => ['nullable', 'url'],
        ];
    }

    /**
     * 29-sep-2026: con proveedor "local", el host de base_url debe estar en
     * helpdeskagents.local_llm_allowed_hosts (vacía = no se permite ninguno).
     *
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('provider') !== 'local' || $validator->errors()->has('base_url')) {
                    return;
                }

                $host = strtolower(trim((string) parse_url((string) ($this->input('base_url') ?: 'http://localhost:11434'), PHP_URL_HOST), '[]'));
                $allowed = array_map('strtolower', (array) config('helpdeskagents.local_llm_allowed_hosts', []));

                if ($host === '' || ! in_array($host, $allowed, true)) {
                    $validator->errors()->add('base_url', $allowed === []
                        ? 'El proveedor local está desactivado: falta HELPDESKAGENTS_LOCAL_LLM_ALLOWED_HOSTS en la configuración.'
                        : 'El host de la URL base no está en la lista de hosts permitidos.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'provider.required' => 'El proveedor es obligatorio.',
            'provider.in' => 'El proveedor seleccionado no es válido.',
            'model.required' => 'El modelo es obligatorio.',
            'model.max' => 'El modelo no puede superar los 255 caracteres.',
            'personality.required' => 'La personalidad es obligatoria.',
            'personality.max' => 'La personalidad no puede superar los 10,000 caracteres.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'api_key.max' => 'La clave API no puede superar los 500 caracteres.',
            'temperature.numeric' => 'La temperatura debe ser un número.',
            'temperature.min' => 'La temperatura mínima es 0.',
            'temperature.max' => 'La temperatura máxima es 2.',
            'max_tokens.integer' => 'El máximo de tokens debe ser un número entero.',
            'max_tokens.min' => 'El máximo de tokens mínimo es 1.',
            'max_tokens.max' => 'El máximo de tokens no puede superar 128,000.',
            'top_p.numeric' => 'El top P debe ser un número.',
            'top_p.min' => 'El top P mínimo es 0.',
            'top_p.max' => 'El top P máximo es 1.',
            'frequency_penalty.numeric' => 'La penalización de frecuencia debe ser un número.',
            'frequency_penalty.min' => 'La penalización de frecuencia mínima es -2.',
            'frequency_penalty.max' => 'La penalización de frecuencia máxima es 2.',
            'presence_penalty.numeric' => 'La penalización de presencia debe ser un número.',
            'presence_penalty.min' => 'La penalización de presencia mínima es -2.',
            'presence_penalty.max' => 'La penalización de presencia máxima es 2.',
            'organization_id.max' => 'El ID de organización no puede superar los 255 caracteres.',
            'version.max' => 'La versión no puede superar los 255 caracteres.',
            'base_url.url' => 'La URL base no tiene un formato válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'provider' => 'proveedor',
            'model' => 'modelo',
            'personality' => 'personalidad',
            'status' => 'estado',
            'api_key' => 'clave API',
            'temperature' => 'temperatura',
            'max_tokens' => 'máximo de tokens',
            'top_p' => 'top P',
            'frequency_penalty' => 'penalización de frecuencia',
            'presence_penalty' => 'penalización de presencia',
            'organization_id' => 'ID de organización',
            'version' => 'versión',
            'base_url' => 'URL base',
        ];
    }
}
