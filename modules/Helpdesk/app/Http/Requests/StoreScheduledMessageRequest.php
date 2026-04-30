<?php

namespace Modules\Helpdesk\Http\Requests;

class StoreScheduledMessageRequest extends StoreConversationMessageRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'body' => ['required', 'string'],
            'scheduled_at' => ['required', 'date', 'after:'.now()->addMinutes(5)->toDateTimeString()],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'scheduled_at.required' => 'La fecha de envío programado es obligatoria.',
            'scheduled_at.date' => 'La fecha de envío programado no es válida.',
            'scheduled_at.after' => 'El envío debe programarse al menos 5 minutos en el futuro.',
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'scheduled_at' => 'fecha de envío',
        ]);
    }
}
