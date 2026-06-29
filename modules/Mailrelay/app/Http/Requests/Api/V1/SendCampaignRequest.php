<?php

namespace Modules\Mailrelay\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SendCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via $this->authorize() in controller
    }

    public function rules(): array
    {
        return [
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.email' => ['required', 'email'],
            'send_async' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipients.required' => 'Los destinatarios son obligatorios.',
            'recipients.min' => 'Debe haber al menos un destinatario.',
            'recipients.*.email.required' => 'El correo del destinatario es obligatorio.',
            'recipients.*.email.email' => 'El correo del destinatario no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'recipients' => 'destinatarios',
            'send_async' => 'envío asíncrono',
        ];
    }
}
