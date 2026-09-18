<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendHsmRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $this->user()?->can('helpdesk.conversations.update')
            && $this->user()?->can('update', $conversation) ?? false;
    }

    public function rules(): array
    {
        return [
            'template_name' => ['required', 'string', 'max:255'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['nullable', 'string', 'max:1000'],
            'language' => ['nullable', 'string', 'in:es,en,pt'],
        ];
    }

    public function messages(): array
    {
        return [
            'template_name.required' => 'El nombre de la plantilla es obligatorio.',
            'template_name.max' => 'El nombre de la plantilla no puede superar los 255 caracteres.',
            'variables.array' => 'Las variables deben ser un listado.',
            'language.in' => 'El idioma debe ser: es, en o pt.',
        ];
    }

    public function attributes(): array
    {
        return [
            'template_name' => 'plantilla',
            'variables' => 'variables',
            'language' => 'idioma',
        ];
    }
}
