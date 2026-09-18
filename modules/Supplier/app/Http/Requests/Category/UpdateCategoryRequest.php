<?php

namespace Modules\Supplier\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.configure');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'sport_id' => ['nullable', 'integer', 'exists:supplier_sports,id'],
            'available' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'short_name.max' => 'El nombre corto no puede superar los 255 caracteres.',
            'sport_id.exists' => 'El deporte seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'short_name' => 'nombre corto',
            'sport_id' => 'deporte',
            'available' => 'estado activo',
        ];
    }
}
