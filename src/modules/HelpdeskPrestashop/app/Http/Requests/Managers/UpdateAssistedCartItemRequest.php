<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssistedCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.update');
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.max' => 'La cantidad máxima es 999.',
        ];
    }

    public function attributes(): array
    {
        return [
            'quantity' => 'cantidad',
        ];
    }
}
