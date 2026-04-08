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
            'popup_enabled' => ['nullable', 'boolean'],
            'popup_title' => ['nullable', 'string', 'max:255'],
            'popup_subtitle' => ['nullable', 'string', 'max:255'],
            'popup_description' => ['nullable', 'string', 'max:1000'],
            'popup_delay' => ['nullable', 'integer', 'min:0', 'max:60'],
            'mailjet_enabled' => ['nullable', 'boolean'],
            'mailjet_api_key' => ['nullable', 'string', 'max:255'],
            'mailjet_api_secret' => ['nullable', 'string', 'max:255'],
            'mailjet_list_id' => ['nullable', 'string', 'max:50'],
        ];
    }
}
