<?php

namespace Modules\Mailer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMailerTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.templates.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'layout_id' => ['nullable', 'exists:mailer_layouts,id'],
            'is_enabled' => ['nullable', 'boolean'],
            'is_protected' => ['nullable', 'boolean'],
            'lang_id' => ['required', 'exists:langs,id'],
            'description' => ['nullable', 'string'],
            'translation_uid' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'preheader.max' => 'El preheader no puede superar los 255 caracteres.',
            'layout_id.exists' => 'El layout seleccionado no existe.',
            'lang_id.required' => 'El idioma es obligatorio.',
            'lang_id.exists' => 'El idioma seleccionado no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'preheader' => 'preheader',
            'content' => 'contenido',
            'layout_id' => 'layout',
            'is_enabled' => 'habilitada',
            'is_protected' => 'protegida',
            'lang_id' => 'idioma',
            'description' => 'descripcion',
        ];
    }
}
