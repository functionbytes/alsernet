<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class EmailTranscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'customer_email' => ['nullable', 'string', 'email'],
            'customer_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo de destino es obligatorio.',
            'email.email' => 'El correo de destino no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo',
            'customer_email' => 'correo del cliente',
            'customer_id' => 'identificador del cliente',
        ];
    }
}
