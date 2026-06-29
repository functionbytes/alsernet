<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignAttentionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'required_without:user_id', 'exists:attention_departments,id'],
            'user_id' => ['nullable', 'required_without:department_id', 'exists:users,id'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'department_id.required_without' => 'Debe especificar un departamento o un usuario.',
            'department_id.exists' => 'El departamento seleccionado no existe.',

            'user_id.required_without' => 'Debe especificar un usuario o un departamento.',
            'user_id.exists' => 'El usuario seleccionado no existe.',

            'comment.string' => 'El comentario debe ser una cadena de texto.',
            'comment.max' => 'El comentario no puede exceder los 500 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'department_id' => 'departamento',
            'user_id' => 'usuario',
            'comment' => 'comentario',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar que no se envíen ambos al mismo tiempo si se desea una asignación exclusiva
            if ($this->filled('department_id') && $this->filled('user_id')) {
                // Esta validación es opcional, se puede permitir ambos
                // Descomentar si se desea solo uno a la vez:
                // $validator->errors()->add('user_id', 'No puede asignar a un departamento y un usuario al mismo tiempo.');
            }
        });
    }
}
