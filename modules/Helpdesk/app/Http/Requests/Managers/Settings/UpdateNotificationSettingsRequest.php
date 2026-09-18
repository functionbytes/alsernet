<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'notify_new_conversation' => ['nullable', 'boolean'],
            'notify_conversation_assigned' => ['nullable', 'boolean'],
            'notify_conversation_resolved' => ['nullable', 'boolean'],
            'notify_new_message' => ['nullable', 'boolean'],
            'notify_overdue_sla' => ['nullable', 'boolean'],
            'email_notifications_enabled' => ['nullable', 'boolean'],
            'browser_notifications_enabled' => ['nullable', 'boolean'],
            'notification_sound_enabled' => ['nullable', 'boolean'],
            'daily_digest_enabled' => ['nullable', 'boolean'],
            'daily_digest_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'daily_digest_time.required' => 'La hora del resumen diario es obligatoria.',
            'daily_digest_time.regex' => 'La hora del resumen diario debe tener el formato HH:MM.',
        ];
    }

    public function attributes(): array
    {
        return [
            'notify_new_conversation' => 'notificar nueva conversación',
            'notify_conversation_assigned' => 'notificar conversación asignada',
            'notify_conversation_resolved' => 'notificar conversación resuelta',
            'notify_new_message' => 'notificar nuevo mensaje',
            'notify_overdue_sla' => 'notificar SLA vencido',
            'email_notifications_enabled' => 'notificaciones por correo',
            'browser_notifications_enabled' => 'notificaciones del navegador',
            'notification_sound_enabled' => 'sonido de notificación',
            'daily_digest_enabled' => 'resumen diario',
            'daily_digest_time' => 'hora del resumen diario',
        ];
    }
}
