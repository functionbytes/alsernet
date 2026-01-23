<?php

namespace Modules\Role\Http\Requests\Systems;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'guard_name' => [
                'required',
                Rule::in(['web', 'api']),
            ],
            'permissions' => [
                'nullable',
                'array',
            ],
            'permissions.*' => [
                'exists:permissions,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'El nombre del rol ya existe en el sistema.',
            'name.min' => 'El nombre debe contener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede exceder los 50 caracteres.',
            'description.max' => 'La descripción no puede exceder los 255 caracteres.',
            'guard_name.required' => 'El guard es obligatorio.',
            'guard_name.in' => 'El guard debe ser "web" o "api".',
            'permissions.*.exists' => 'Uno o más permisos no existen en el sistema.',
        ];
    }
}
