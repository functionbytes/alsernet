<?php

namespace Modules\Chat\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class BulkRemoveLabelRequest extends FormRequest
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
            'label_ids' => 'required|array|min:1',
            'label_ids.*' => 'exists:chat_labels,id',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_ids.required' => 'Debe seleccionar al menos un contacto.',
            'customer_ids.array' => 'Los IDs de contacto deben ser un arreglo.',
            'customer_ids.min' => 'Debe seleccionar al menos un contacto.',
            'customer_ids.*.exists' => 'Uno o más contactos seleccionados no existen.',
            'label_ids.required' => 'Debe seleccionar al menos una etiqueta para remover.',
            'label_ids.array' => 'Los IDs de etiqueta deben ser un arreglo.',
            'label_ids.min' => 'Debe seleccionar al menos una etiqueta para remover.',
            'label_ids.*.exists' => 'Una o más etiquetas seleccionadas no existen.',
        ];
    }
}
