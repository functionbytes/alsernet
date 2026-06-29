<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpecificationGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.specification-groups.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'description' => ['nullable', 'string', 'max:400'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 250 caracteres.',
            'description.max' => 'La descripcion no puede superar los 400 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
        ];
    }
}
