<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enable_email_notifications' => 'boolean',
            'notify_on_received' => 'boolean',
            'notify_on_in_process' => 'boolean',
            'notify_on_resolved' => 'boolean',
            'notify_on_closed' => 'boolean',
            'notify_assigned_user' => 'boolean',
            'mail_template_received_id' => 'nullable|integer|exists:mailer_templates,id',
            'mail_template_in_process_id' => 'nullable|integer|exists:mailer_templates,id',
            'mail_template_resolved_id' => 'nullable|integer|exists:mailer_templates,id',
            'mail_template_closed_id' => 'nullable|integer|exists:mailer_templates,id',
            'mail_template_assigned_id' => 'nullable|integer|exists:mailer_templates,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'enable_email_notifications' => 'notificaciones por email',
            'notify_on_received' => 'notificar al recibir',
            'notify_on_in_process' => 'notificar al procesar',
            'notify_on_resolved' => 'notificar al resolver',
            'notify_on_closed' => 'notificar al cerrar',
            'notify_assigned_user' => 'notificar al usuario asignado',
            'mail_template_received_id' => 'plantilla: caso recibido',
            'mail_template_in_process_id' => 'plantilla: en proceso',
            'mail_template_resolved_id' => 'plantilla: resuelto',
            'mail_template_closed_id' => 'plantilla: cerrado',
            'mail_template_assigned_id' => 'plantilla: asignado',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mail_template_received_id.exists' => 'La :attribute seleccionada no existe.',
            'mail_template_in_process_id.exists' => 'La :attribute seleccionada no existe.',
            'mail_template_resolved_id.exists' => 'La :attribute seleccionada no existe.',
            'mail_template_closed_id.exists' => 'La :attribute seleccionada no existe.',
            'mail_template_assigned_id.exists' => 'La :attribute seleccionada no existe.',
        ];
    }
}
