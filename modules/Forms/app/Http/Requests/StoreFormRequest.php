<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:form_categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'success_message' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'submit_button_text' => ['nullable', 'string', 'max:100'],
            'button_position' => ['nullable', 'in:left,center,right,full'],
            'button_size' => ['nullable', 'in:sm,md,lg'],
            'button_variant' => ['nullable', 'string', 'max:50'],
            'button_icon' => ['nullable', 'string', 'max:50'],
            'success_animation' => ['nullable', 'in:none,fade,checkmark,confetti'],
            'progress_bar_style' => ['nullable', 'in:bar,dots,steps,percentage'],
            'theme' => ['nullable', 'string', 'max:50'],
            'custom_css' => ['nullable', 'string'],
            'custom_js' => ['nullable', 'string'],
            'floating_label' => ['boolean'],
            'send_confirmation' => ['boolean'],
            'email_field_key' => ['nullable', 'string', 'max:100'],
            'confirmation_subject' => ['nullable', 'string', 'max:255'],
            'confirmation_message' => ['nullable', 'string'],
            'admin_notification_email' => ['nullable', 'string', 'max:500'],
            'honeypot_enabled' => ['boolean'],
            'captcha_enabled' => ['boolean'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'max_submissions' => ['nullable', 'integer', 'min:1'],
            'retention_days' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
