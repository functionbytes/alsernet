<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketEmailBlacklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'value' => strtolower(trim((string) $this->input('value'))),
        ]);
    }

    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'type' => ['required', 'in:email,domain'],
            'value' => [
                'required',
                'string',
                'max:255',
                $type === 'domain'
                    ? 'regex:/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/'
                    : 'email:filter',
                Rule::unique('helpdesk.helpdesk_ticket_email_blacklist', 'value')->where('type', $type),
            ],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo debe ser email o dominio.',
            'value.required' => 'El valor es obligatorio.',
            'value.email' => 'Introduce un email válido.',
            'value.regex' => 'Introduce un dominio válido (por ejemplo: spam.com).',
            'value.unique' => 'Ese email o dominio ya está en la lista negra.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo',
            'value' => 'valor',
            'reason' => 'motivo',
        ];
    }
}
