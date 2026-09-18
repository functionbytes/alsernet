<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class SendHsmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.update');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'template_name' => ['required', 'string', 'max:255'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['nullable', 'string', 'max:1000'],
            'language' => ['nullable', 'string', 'in:es,en,pt'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template_name.required' => 'El nombre de la plantilla es obligatorio.',
            'template_name.max' => 'El nombre de la plantilla no puede superar los 255 caracteres.',
            'variables.array' => 'Las variables deben ser un listado.',
            'language.in' => 'El idioma debe ser: es, en o pt.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'template_name' => 'plantilla',
            'variables' => 'variables',
            'language' => 'idioma',
        ];
    }
}
