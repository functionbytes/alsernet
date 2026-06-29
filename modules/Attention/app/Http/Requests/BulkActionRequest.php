<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionRequest extends FormRequest
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
            'action' => ['required', 'in:assign,close,delete'],
            'attention_ids' => ['required', 'array', 'min:1'],
            'attention_ids.*' => ['exists:attentions,id'],
            'department_id' => ['required_if:action,assign', 'nullable', 'exists:attention_departments,id'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción debe ser: asignar, cerrar o eliminar.',

            'attention_ids.required' => 'Debe seleccionar al menos una atención.',
            'attention_ids.array' => 'Las atenciones deben ser un arreglo.',
            'attention_ids.min' => 'Debe seleccionar al menos una atención.',
            'attention_ids.*.exists' => 'Una o más atenciones seleccionadas no existen.',

            'department_id.required_if' => 'El departamento es obligatorio cuando la acción es asignar.',
            'department_id.exists' => 'El departamento seleccionado no existe.',

            'user_id.exists' => 'El usuario seleccionado no existe.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'action' => 'acción',
            'attention_ids' => 'atenciones',
            'department_id' => 'departamento',
            'user_id' => 'usuario',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validaciones adicionales según la acción
            $action = $this->input('action');

            if ($action === 'assign') {
                // Para asignar se requiere department_id o user_id
                if (! $this->filled('department_id') && ! $this->filled('user_id')) {
                    $validator->errors()->add('department_id', 'Debe especificar un departamento o un usuario para asignar.');
                }
            }

            if ($action === 'delete') {
                // Validar que el usuario tenga permisos para eliminar (esto se puede manejar en el controlador)
                // Aquí solo validamos la estructura de datos
            }
        });
    }
}
