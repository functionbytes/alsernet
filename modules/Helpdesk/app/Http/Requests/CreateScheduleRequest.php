<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $this->user()?->can('helpdesk.conversations.update')
            && $this->user()?->can('update', $conversation) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'type' => ['required', 'in:meeting,reminder,callback,task,message'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título del evento es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'scheduled_at.required' => 'La fecha y hora son obligatorias.',
            'scheduled_at.date' => 'La fecha y hora no tienen un formato válido.',
            'scheduled_at.after' => 'La fecha y hora deben ser en el futuro.',
            'type.required' => 'El tipo de evento es obligatorio.',
            'type.in' => 'El tipo debe ser: meeting, reminder, callback, task o message.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'scheduled_at' => 'fecha y hora',
            'type' => 'tipo',
            'notes' => 'notas',
        ];
    }
}
