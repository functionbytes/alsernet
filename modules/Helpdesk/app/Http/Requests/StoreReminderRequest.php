<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.view');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'remind_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'email_notify' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'remind_at.required' => 'La fecha del recordatorio es obligatoria.',
            'remind_at.after' => 'La fecha del recordatorio debe ser futura.',
            'notes.max' => 'Las notas no pueden superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'remind_at' => 'fecha',
            'notes' => 'notas',
        ];
    }
}
