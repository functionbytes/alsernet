<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TestAiAgentConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.aiagents.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:openai,anthropic,gemini,local'],
            'api_key' => ['nullable', 'string'],
            'model' => ['required', 'string'],
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
            'provider.required' => 'El proveedor es obligatorio.',
            'provider.in' => 'El proveedor seleccionado no es válido.',
            'model.required' => 'El modelo es obligatorio.',
            'base_url.url' => 'La URL base no tiene un formato válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'provider' => 'proveedor',
            'api_key' => 'clave API',
            'model' => 'modelo',
            'base_url' => 'URL base',
        ];
    }
}
