<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class MarkAsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'up_to_message_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'customer_email' => ['nullable', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'up_to_message_id.integer' => 'El ID de mensaje debe ser un entero.',
            'up_to_message_id.exists' => 'El mensaje especificado no existe.',
            'customer_email.email' => 'El email del cliente no tiene un formato válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'up_to_message_id' => 'ID de mensaje',
            'customer_id' => 'ID de cliente',
            'customer_email' => 'email del cliente',
        ];
    }
}
