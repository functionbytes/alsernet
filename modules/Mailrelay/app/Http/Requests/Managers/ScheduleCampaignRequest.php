<?php

namespace Modules\Mailrelay\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.campaigns.send');
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'recipients' => ['required', 'array', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.required' => 'La fecha de programación es obligatoria.',
            'scheduled_at.date' => 'La fecha de programación no es válida.',
            'scheduled_at.after' => 'La fecha de programación debe ser una fecha futura.',
            'recipients.required' => 'Debe especificar al menos un destinatario.',
            'recipients.min' => 'Debe especificar al menos un destinatario.',
        ];
    }

    public function attributes(): array
    {
        return [
            'scheduled_at' => 'fecha de programación',
            'recipients' => 'destinatarios',
        ];
    }
}
