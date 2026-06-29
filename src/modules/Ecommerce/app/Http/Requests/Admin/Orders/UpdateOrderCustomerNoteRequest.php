<?php

namespace Modules\Ecommerce\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderCustomerNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_note.string' => 'La nota debe ser texto.',
            'customer_note.max' => 'La nota no puede tener mas de :max caracteres.',
        ];
    }
}
