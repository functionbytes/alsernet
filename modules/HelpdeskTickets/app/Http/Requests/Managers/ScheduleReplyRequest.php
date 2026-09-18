<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleReplyRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:20000'],
            'deliver_at' => ['required', 'date', 'after:now'],
            'is_internal' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'El cuerpo de la respuesta es obligatorio.',
            'body.max' => 'La respuesta no puede superar los 20000 caracteres.',
            'deliver_at.required' => 'La fecha de envío es obligatoria.',
            'deliver_at.after' => 'La fecha de envío debe ser futura.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => 'respuesta',
            'deliver_at' => 'fecha de envío',
            'is_internal' => 'nota interna',
        ];
    }
}
