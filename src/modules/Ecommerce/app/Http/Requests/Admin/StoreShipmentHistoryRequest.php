<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'La descripcion del historial es obligatoria.',
            'description.max' => 'La descripcion no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'description' => 'descripcion',
        ];
    }
}
