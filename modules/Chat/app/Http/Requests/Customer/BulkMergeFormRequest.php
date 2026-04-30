<?php

namespace Modules\Chat\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class BulkMergeFormRequest extends FormRequest
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
            'contacts' => 'required|array|min:2',
            'contacts.*' => 'exists:chat_customers,id',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contacts.required' => 'Debe seleccionar clientes para fusionar.',
            'contacts.array' => 'Los clientes deben ser un array.',
            'contacts.min' => 'Debe seleccionar al menos 2 clientes para fusionar.',
            'contacts.*.exists' => 'Uno o más clientes seleccionados no existen.',
        ];
    }
}
