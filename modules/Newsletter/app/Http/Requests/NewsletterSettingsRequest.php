<?php

namespace Modules\Newsletter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewsletterSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email_notifications' => ['nullable', 'boolean'],
            'recaptcha_enabled' => ['nullable', 'boolean'],
            'popup_enabled' => ['nullable', 'boolean'],
            'popup_delay' => ['nullable', 'integer', 'min:0', 'max:60'],
            'mailjet_enabled' => ['nullable', 'boolean'],
            'mailjet_api_key' => ['nullable', 'string', 'max:255'],
            'mailjet_api_secret' => ['nullable', 'string', 'max:255'],
            'mailjet_list_id' => ['nullable', 'string', 'max:50'],
            'email_subscription_enabled' => ['nullable', 'boolean'],
            'email_subscription_template_id' => ['nullable', 'integer', 'exists:mailer_templates,id'],
            'email_unsubscription_enabled' => ['nullable', 'boolean'],
            'email_unsubscription_template_id' => ['nullable', 'integer', 'exists:mailer_templates,id'],
        ];
    }
}
