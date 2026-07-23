<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSideConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.tickets.update');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'participant_type' => ['required', 'in:team,external_email'],
            'participant_user_id' => ['nullable', 'integer', 'exists:mariadb.users,id'],
            'participant_email' => ['nullable', 'email', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('participant_type');

            if ($type === 'team' && ! $this->filled('participant_user_id')) {
                $validator->errors()->add('participant_user_id', 'Debes elegir un compañero.');
            }

            if ($type === 'external_email' && ! $this->filled('participant_email')) {
                $validator->errors()->add('participant_email', 'Debes indicar el email del contacto externo.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'participant_type.in' => 'El tipo de participante no es válido.',
            'body.required' => 'El mensaje es obligatorio.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'participant_user_id' => 'compañero',
            'participant_email' => 'email',
            'body' => 'mensaje',
        ];
    }
}
