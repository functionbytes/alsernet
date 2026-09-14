<?php

namespace Modules\Supplier\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ToggleSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.configure') ?? false;
    }

    public function rules(): array
    {
        return [
            'available' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'available.required' => 'El campo disponibilidad es obligatorio.',
            'available.boolean' => 'El campo disponibilidad debe ser verdadero o falso.',
        ];
    }
}
