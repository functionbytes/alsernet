<?php

namespace Modules\Mailrelay\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleCampaignApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via $this->authorize() in controller
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
            'scheduled_at.after' => 'La fecha de programación debe ser posterior a la fecha actual.',
            'recipients.required' => 'Los destinatarios son obligatorios.',
            'recipients.min' => 'Debe haber al menos un destinatario.',
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
