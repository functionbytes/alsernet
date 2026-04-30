<?php

namespace Modules\Chat\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:chat_customers,id',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_ids.required' => 'Debe seleccionar al menos un contacto para eliminar.',
            'customer_ids.array' => 'Los IDs de contacto deben ser un arreglo.',
            'customer_ids.min' => 'Debe seleccionar al menos un contacto para eliminar.',
            'customer_ids.*.exists' => 'Uno o más contactos seleccionados no existen.',
        ];
    }
}
