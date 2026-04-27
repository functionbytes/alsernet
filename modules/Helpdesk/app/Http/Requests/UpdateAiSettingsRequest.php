<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'llm_provider' => ['required', 'in:openai,anthropic,gemini'],
            'openai_api_key' => ['nullable', 'string'],
            'openai_model' => ['nullable', 'string'],
            'anthropic_api_key' => ['nullable', 'string'],
            'anthropic_model' => ['nullable', 'string'],
            'gemini_api_key' => ['nullable', 'string'],
            'gemini_model' => ['nullable', 'string'],
            'embeddings_provider' => ['required', 'in:openai,gemini'],
            'enable_embeddings' => ['boolean'],
            'enable_rag' => ['boolean'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['required', 'integer', 'min:100', 'max:128000'],
            'top_p' => ['required', 'numeric', 'min:0', 'max:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'llm_provider.required' => 'El proveedor de IA es obligatorio.',
            'llm_provider.in' => 'El proveedor seleccionado no es válido.',
            'embeddings_provider.required' => 'El proveedor de embeddings es obligatorio.',
            'embeddings_provider.in' => 'El proveedor de embeddings no es válido.',
            'temperature.required' => 'La temperatura es obligatoria.',
            'temperature.min' => 'La temperatura mínima es 0.',
            'temperature.max' => 'La temperatura máxima es 2.',
            'max_tokens.required' => 'El máximo de tokens es obligatorio.',
            'max_tokens.min' => 'El mínimo de tokens es 100.',
            'max_tokens.max' => 'El máximo de tokens permitido es 128000.',
            'top_p.required' => 'El top_p es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'llm_provider' => 'proveedor de IA',
            'embeddings_provider' => 'proveedor de embeddings',
            'temperature' => 'temperatura',
            'max_tokens' => 'máximo de tokens',
            'top_p' => 'top P',
        ];
    }
}
