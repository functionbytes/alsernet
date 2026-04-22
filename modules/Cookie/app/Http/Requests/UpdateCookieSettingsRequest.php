<?php

namespace Modules\Cookie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCookieSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cookie.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'enabled' => 'nullable|in:1',
            'style' => 'nullable|in:full-width,minimal',
            'position' => 'nullable|in:bottom,top,center',
            'google_analytics_enabled' => 'nullable|in:1',
            'google_analytics_id' => ['nullable', 'string', 'max:30', 'regex:/^(G|UA|AW|DC)-[A-Za-z0-9-]+$/i'],
            'facebook_pixel_enabled' => 'nullable|in:1',
            'facebook_pixel_id' => 'nullable|digits_between:1,20',
            'google_tag_manager_enabled' => 'nullable|in:1',
            'google_tag_manager_id' => ['nullable', 'string', 'max:20', 'regex:/^GTM-[A-Z0-9]+$/'],
            'microsoft_uet_enabled' => 'nullable|in:1',
            'microsoft_uet_id' => ['nullable', 'string', 'max:50'],
            'linkedin_insight_enabled' => 'nullable|in:1',
            'linkedin_partner_id' => ['nullable', 'string', 'max:20'],
            'tiktok_pixel_enabled' => 'nullable|in:1',
            'tiktok_pixel_id' => ['nullable', 'string', 'max:50'],
            'twitter_pixel_enabled' => 'nullable|in:1',
            'twitter_pixel_id' => ['nullable', 'string', 'max:20'],
            'geo_targeting_enabled' => 'nullable|in:1',
            'consent_version' => 'nullable|string|max:20',
            'log_retention_days' => 'nullable|integer|min:7|max:3650',
            'alert_threshold' => 'nullable|integer|min:0|max:100',
            'policy_page_id' => 'nullable|integer|exists:pages,id',
        ];
    }

    public function messages(): array
    {
        return [
            'position.in' => 'La posición debe ser inferior, superior o centro.',
            'google_analytics_id.regex' => 'El ID de Google Analytics debe tener el formato G-XXXXXXXXXX o UA-XXXXXXXX.',
            'google_analytics_id.max' => 'El ID de Google Analytics no puede superar los 30 caracteres.',
            'facebook_pixel_id.digits_between' => 'El Facebook Pixel ID debe contener solo números.',
            'google_tag_manager_id.regex' => 'El ID de GTM debe tener el formato GTM-XXXXXXX (letras mayúsculas y números).',
            'google_tag_manager_id.max' => 'El ID de GTM no puede superar los 20 caracteres.',
            'microsoft_uet_id.max' => 'El ID de UET no puede superar los 50 caracteres.',
            'linkedin_partner_id.max' => 'El LinkedIn Partner ID no puede superar los 20 caracteres.',
            'tiktok_pixel_id.max' => 'El TikTok Pixel ID no puede superar los 50 caracteres.',
            'twitter_pixel_id.max' => 'El Twitter/X Pixel ID no puede superar los 20 caracteres.',
            'consent_version.max' => 'La versión no puede superar los 20 caracteres.',
            'log_retention_days.integer' => 'Los días de retención deben ser un número entero.',
            'log_retention_days.min' => 'El mínimo de retención es 7 días.',
            'log_retention_days.max' => 'El máximo de retención es 3650 días.',
            'alert_threshold.integer' => 'El umbral debe ser un número entero entre 0 y 100.',
        ];
    }
}
