<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeoSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.settings.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // IndexNow
            'indexnow_enabled' => ['sometimes', 'boolean'],
            'indexnow_key' => ['nullable', 'string', 'min:8', 'max:128', 'regex:/^[a-zA-Z0-9]+$/'],
            'indexnow_auto_submit' => ['sometimes', 'boolean'],

            // Webhooks
            'slack_webhook_url' => ['nullable', 'url', 'max:500'],
            'discord_webhook_url' => ['nullable', 'url', 'max:500'],
            'webhook_signing_secret' => ['nullable', 'string', 'min:16', 'max:128'],
            'notify_score_drop' => ['sometimes', 'boolean'],
            'notify_redirect_chain' => ['sometimes', 'boolean'],
            'notify_orphans' => ['sometimes', 'boolean'],
            'score_drop_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'notifications_email' => ['nullable', 'email', 'max:255'],

            // PageSpeed
            'pagespeed_api_key' => ['nullable', 'string', 'max:100'],

            // Performance hints
            'preconnect' => ['nullable', 'string', 'max:1000'],
            'dns_prefetch' => ['nullable', 'string', 'max:1000'],
            'html_cache_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'enable_security_headers' => ['sometimes', 'boolean'],

            // Web Vitals
            'web_vitals_enabled' => ['sometimes', 'boolean'],
            'web_vitals_sample_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'web_vitals_retention_days' => ['nullable', 'integer', 'min:1', 'max:365'],

            // llms.txt
            'llms_txt_enabled' => ['sometimes', 'boolean'],

            // GTM
            'gtm_container_id' => ['nullable', 'string', 'max:20', 'regex:/^GTM-[A-Z0-9]+$/i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'indexnow_key.min' => 'La key debe tener al menos 8 caracteres.',
            'indexnow_key.regex' => 'La key solo puede contener letras y números.',
            'webhook_signing_secret.min' => 'El secret debe tener al menos 16 caracteres.',
            'web_vitals_sample_rate.max' => 'El sample rate debe estar entre 0 y 1.',
        ];
    }
}
