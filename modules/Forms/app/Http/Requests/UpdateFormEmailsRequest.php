<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormEmailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.settings.manage');
    }

    public function rules(): array
    {
        return [
            'admin_notification_email' => ['nullable', 'string', 'max:500'],
            'send_confirmation' => ['boolean'],
            'email_field_key' => ['nullable', 'string', 'max:100'],
            'confirmation_subject' => ['nullable', 'string', 'max:255'],
            'confirmation_message' => ['nullable', 'string'],
            'honeypot_enabled' => ['boolean'],
            'captcha_enabled' => ['boolean'],
            'admin_template_id' => ['nullable', 'integer'],
            'confirmation_template_id' => ['nullable', 'integer'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'follow_up' => ['nullable', 'array'],
            'follow_up.name' => ['required_with:follow_up', 'string', 'max:100'],
            'follow_up.send_after_days' => ['required_with:follow_up', 'integer', 'min:1'],
            'follow_up.email_template_id' => ['required_with:follow_up', 'integer'],
            'follow_up.recipient_type' => ['required_with:follow_up', 'in:submitter,admin'],
            'follow_up.is_active' => ['boolean'],
            'conditional_email' => ['nullable', 'array'],
            'conditional_email.condition_field_key' => ['required_with:conditional_email', 'string', 'max:100'],
            'conditional_email.condition_operator' => ['required_with:conditional_email', 'in:equals,not_equals,contains,starts_with,ends_with'],
            'conditional_email.condition_value' => ['required_with:conditional_email', 'string'],
            'conditional_email.admin_template_id' => ['nullable', 'integer'],
            'conditional_email.client_template_id' => ['nullable', 'integer'],
            'conditional_email.is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'webhook_url.url' => 'La URL del webhook no es válida.',
            'follow_up.send_after_days.min' => 'El follow-up debe enviarse al menos 1 día después.',
            'conditional_email.condition_operator.in' => 'El operador condicional no es válido.',
        ];
    }
}
