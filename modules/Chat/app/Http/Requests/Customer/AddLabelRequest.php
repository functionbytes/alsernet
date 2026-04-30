<?php

namespace Modules\Chat\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class AddLabelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('customer'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'label_id' => 'required|exists:chat_labels,id',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'label_id.required' => 'Debe seleccionar una etiqueta.',
            'label_id.exists' => 'La etiqueta seleccionada no existe.',
        ];
    }
}
