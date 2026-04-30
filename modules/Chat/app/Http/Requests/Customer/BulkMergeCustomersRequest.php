<?php

namespace Modules\Chat\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class BulkMergeCustomersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'primary_contact_id' => 'required|exists:chat_customers,id',
            'merge_contact_ids' => 'required|array|min:1',
            'merge_contact_ids.*' => 'exists:chat_customers,id',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'primary_contact_id.required' => 'El cliente principal es requerido.',
            'primary_contact_id.exists' => 'El cliente principal seleccionado no existe.',
            'merge_contact_ids.required' => 'Debe seleccionar al menos un cliente para fusionar.',
            'merge_contact_ids.array' => 'Los clientes a fusionar deben ser un array.',
            'merge_contact_ids.min' => 'Debe seleccionar al menos un cliente para fusionar.',
            'merge_contact_ids.*.exists' => 'Uno o más clientes seleccionados no existen.',
        ];
    }
}
