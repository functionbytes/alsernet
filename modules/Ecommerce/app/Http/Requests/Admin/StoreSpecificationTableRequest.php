<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpecificationTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.specification-tables.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'description' => ['nullable', 'string', 'max:400'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['exists:ecommerce_specification_groups,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 250 caracteres.',
            'description.max' => 'La descripcion no puede superar los 400 caracteres.',
            'groups.*.exists' => 'Uno o mas grupos seleccionados no son validos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'groups' => 'grupos',
        ];
    }
}
