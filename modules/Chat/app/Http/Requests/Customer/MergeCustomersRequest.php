<?php

namespace Modules\Chat\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class MergeCustomersRequest extends FormRequest
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
            'primary_contact_id.required' => 'Debe seleccionar un contacto principal.',
            'primary_contact_id.exists' => 'El contacto principal no existe.',
            'merge_contact_ids.required' => 'Debe seleccionar contactos para fusionar.',
            'merge_contact_ids.array' => 'Los contactos a fusionar deben ser un array.',
            'merge_contact_ids.min' => 'Debe seleccionar al menos 1 contacto para fusionar.',
            'merge_contact_ids.*.exists' => 'Uno o más contactos a fusionar no existen.',
        ];
    }
}
