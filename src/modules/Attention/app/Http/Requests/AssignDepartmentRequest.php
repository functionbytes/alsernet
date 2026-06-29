<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attention.assign') ?? false;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:attention_departments,id'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.required' => 'El departamento es obligatorio.',
            'department_id.exists' => 'El departamento seleccionado no existe.',
            'comment.max' => 'El comentario no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'department_id' => 'departamento',
            'comment' => 'comentario',
        ];
    }
}
