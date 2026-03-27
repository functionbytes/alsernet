<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAttentionDepartmentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150', 'unique:attention_departments,name'],
            'email' => ['nullable', 'email', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'responsible_name' => ['nullable', 'string', 'max:150'],
            'responsible_email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'users' => ['nullable', 'array'],
            'users.*' => ['exists:users,id'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede exceder los 150 caracteres.',
            'name.unique' => 'Ya existe un departamento con este nombre.',

            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no puede exceder los 150 caracteres.',

            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede exceder los 1000 caracteres.',

            'responsible_name.string' => 'El nombre del responsable debe ser una cadena de texto.',
            'responsible_name.max' => 'El nombre del responsable no puede exceder los 150 caracteres.',

            'responsible_email.email' => 'El correo del responsable debe ser una dirección válida.',
            'responsible_email.max' => 'El correo del responsable no puede exceder los 150 caracteres.',

            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'phone.max' => 'El teléfono no puede exceder los 20 caracteres.',

            'users.array' => 'Los usuarios deben ser un arreglo.',
            'users.*.exists' => 'Uno o más usuarios seleccionados no existen.',

            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'description' => 'descripción',
            'responsible_name' => 'nombre del responsable',
            'responsible_email' => 'correo del responsable',
            'phone' => 'teléfono',
            'users' => 'usuarios',
            'is_active' => 'estado activo',
        ];
    }
}
