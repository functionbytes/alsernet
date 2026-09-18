<?php

namespace Modules\Modules\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('modules.manage');
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', 'integer', 'min:0', 'max:999'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'priority.required' => 'La prioridad es obligatoria.',
            'priority.integer' => 'La prioridad debe ser un número entero.',
            'priority.min' => 'La prioridad no puede ser menor a 0.',
            'priority.max' => 'La prioridad no puede superar 999.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'priority' => 'prioridad',
            'description' => 'descripción',
        ];
    }
}
