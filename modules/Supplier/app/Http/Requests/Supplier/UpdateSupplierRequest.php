<?php

namespace Modules\Supplier\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.configure') ?? false;
    }

    public function rules(): array
    {
        return [
            'erp_id' => 'nullable|integer',
            'label' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'priority' => 'nullable|integer|min:0',
            'available' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El nombre del proveedor es obligatorio.',
            'label.max' => 'El nombre no puede tener más de 255 caracteres.',
            'code.required' => 'El código del proveedor es obligatorio.',
            'code.max' => 'El código no puede tener más de 255 caracteres.',
            'email.email' => 'El correo electrónico no es válido.',
            'priority.min' => 'La prioridad no puede ser negativa.',
            'available.boolean' => 'El campo disponible debe ser verdadero o falso.',
        ];
    }
}
