<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SnoozeConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.update');
    }

    public function rules(): array
    {
        return [
            'until' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'until.required' => 'La fecha de posposición es obligatoria.',
            'until.date' => 'La fecha de posposición debe ser una fecha válida.',
            'until.after' => 'La fecha de posposición debe ser posterior al momento actual.',
        ];
    }

    public function attributes(): array
    {
        return [
            'until' => 'fecha de posposición',
        ];
    }
}
