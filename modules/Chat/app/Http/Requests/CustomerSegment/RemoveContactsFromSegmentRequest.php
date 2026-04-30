<?php

namespace Modules\Chat\Http\Requests\CustomerSegment;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Chat\Models\Customers\CustomerSegment;

class RemoveContactsFromSegmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $segment = $this->route('segment');

        if (! $segment instanceof CustomerSegment) {
            return false;
        }

        return auth()->user()->can('update', $segment);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:chat_customers,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_ids.required' => 'Debe proporcionar al menos un cliente.',
            'customer_ids.array' => 'Los IDs de clientes deben ser un array.',
            'customer_ids.min' => 'Debe proporcionar al menos un cliente.',
            'customer_ids.*.integer' => 'Cada ID de cliente debe ser un número entero.',
            'customer_ids.*.exists' => 'Uno o más clientes no existen en el sistema.',
        ];
    }
}
