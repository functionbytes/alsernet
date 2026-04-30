<?php

namespace Modules\Ecommerce\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class AddOrderNoteRequest extends FormRequest
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
            'admin_note' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admin_note.required' => 'La nota es obligatoria.',
            'admin_note.string' => 'La nota debe ser texto.',
            'admin_note.max' => 'La nota no puede tener mas de :max caracteres.',
        ];
    }
}
