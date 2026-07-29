<?php

namespace Modules\Supplier\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.configure');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('supplier_categories', 'code')],
            'description' => ['nullable', 'string'],
            'sport_id' => ['nullable', 'integer', 'exists:supplier_sports,id'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'available' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'code.max' => 'El codigo no puede superar los 100 caracteres.',
            'code.unique' => 'El codigo ya esta en uso.',
            'sport_id.exists' => 'El deporte seleccionado no existe.',
            'priority.min' => 'La prioridad minima es 0.',
            'priority.max' => 'La prioridad maxima es 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'code' => 'codigo',
            'description' => 'descripcion',
            'sport_id' => 'deporte',
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
