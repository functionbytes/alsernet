<?php

namespace Modules\Mailrelay\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SendTestCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via $this->authorize() in controller
    }

    public function rules(): array
    {
        return [
            'test_email' => ['required', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'test_email.required' => 'El correo de prueba es obligatorio.',
            'test_email.email' => 'El correo de prueba no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'test_email' => 'correo de prueba',
        ];
    }
}
