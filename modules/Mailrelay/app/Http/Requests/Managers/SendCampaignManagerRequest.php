<?php

namespace Modules\Mailrelay\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class SendCampaignManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.campaigns.send');
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
            'recipients.required' => 'Debe especificar al menos un destinatario.',
            'recipients.min' => 'Debe especificar al menos un destinatario.',
            'recipients.*.email.required' => 'El correo del destinatario es obligatorio.',
            'recipients.*.email.email' => 'El correo del destinatario no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'recipients' => 'destinatarios',
            'recipients.*.email' => 'correo del destinatario',
            'send_async' => 'envío asíncrono',
        ];
    }
}
