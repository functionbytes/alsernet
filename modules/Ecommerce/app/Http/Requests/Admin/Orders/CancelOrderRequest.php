<?php

namespace Modules\Ecommerce\Http\Requests\Admin\Orders;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
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
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cancel_reason.string' => 'El motivo de cancelacion debe ser texto.',
            'cancel_reason.max' => 'El motivo de cancelacion no puede tener mas de :max caracteres.',
        ];
    }
}
