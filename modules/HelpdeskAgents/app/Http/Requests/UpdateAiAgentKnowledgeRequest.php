<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiAgentKnowledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.aiagents.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:document,faq,article,manual,url'],
            'source_url' => ['nullable', 'url'],
            'source_type' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'summary' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'content.required' => 'El contenido es obligatorio.',
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo seleccionado no es válido.',
            'source_url.url' => 'La URL de origen no tiene un formato válido.',
            'tags.array' => 'Las etiquetas deben ser un conjunto válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'content' => 'contenido',
            'type' => 'tipo',
            'source_url' => 'URL de origen',
            'source_type' => 'tipo de origen',
            'tags' => 'etiquetas',
            'summary' => 'resumen',
            'is_active' => 'activo',
        ];
    }
}
