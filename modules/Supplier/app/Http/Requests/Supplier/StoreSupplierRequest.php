<?php

namespace Modules\Supplier\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.configure');
    }

    public function rules(): array
    {
        return [
            'erp_id' => ['nullable', 'integer', 'min:1'],
            'label' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'available' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El nombre del proveedor es obligatorio.',
            'label.max' => 'El nombre no puede superar los 255 caracteres.',
            'code.required' => 'El código del proveedor es obligatorio.',
            'code.max' => 'El código no puede superar los 100 caracteres.',
            'email.email' => 'El correo electrónico no es válido.',
            'priority.integer' => 'La prioridad debe ser un número entero.',
            'priority.min' => 'La prioridad mínima es 0.',
            'priority.max' => 'La prioridad máxima es 1000.',
        ];
    }

    public function attributes(): array
    {
        return [
            'erp_id' => 'ID del ERP',
            'label' => 'nombre',
            'code' => 'código',
            'description' => 'descripción',
            'email' => 'correo electrónico',
            'priority' => 'prioridad',
            'available' => 'estado activo',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'available' => $this->boolean('available', true),
        ]);
    }
}
