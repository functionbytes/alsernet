<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.templates.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:email,sms'],
            'subject' => ['nullable', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'html_content' => ['nullable', 'string'],
            'thumbnail_url' => ['nullable', 'url', 'max:500'],
            'is_global' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El tipo debe ser email o sms.',
            'thumbnail_url.url' => 'La URL de la miniatura no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'type' => 'tipo',
            'subject' => 'asunto',
            'preheader' => 'preencabezado',
            'html_content' => 'contenido HTML',
            'thumbnail_url' => 'miniatura',
            'is_global' => 'plantilla global',
        ];
    }
}
