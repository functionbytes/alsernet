<?php

namespace Modules\Analytics\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Analytics\Rules\AnalyticsCredentialRule;

class AnalyticsSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust according to authorization policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'google_analytics_enable' => [
                'nullable',
                'boolean',
            ],
            'google_analytics_property_id' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[0-9]+$/',
                'required_if:google_analytics_enable,true',
            ],
            'google_analytics_measurement_id' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^G-[A-Z0-9]+$/',
            ],
            'google_analytics_credentials' => [
                'nullable',
                'string',
                new AnalyticsCredentialRule,
            ],
            'analytics_cache_lifetime' => [
                'nullable',
                'integer',
                'min:1',
                'max:1440', // Max 24 hours in minutes
            ],
            'analytics_reports_daily_enabled' => [
                'nullable',
                'boolean',
            ],
            'analytics_reports_weekly_enabled' => [
                'nullable',
                'boolean',
            ],
            'analytics_reports_monthly_enabled' => [
                'nullable',
                'boolean',
            ],
            'analytics_dashboard_widgets' => [
                'nullable',
                'array',
            ],
            'analytics_dashboard_widgets.*' => [
                'string',
                'in:general,top_pages,top_browsers,top_referrers',
            ],
            'analytics_report_email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'meta_pixel_id' => ['nullable', 'string', 'max:25', 'regex:/^[0-9]+$/'],
            'microsoft_clarity_id' => ['nullable', 'string', 'max:20', 'regex:/^[a-z0-9]+$/i'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:30', 'regex:/^[A-Z0-9]+$/i'],
            'linkedin_insight_tag_id' => ['nullable', 'string', 'max:15', 'regex:/^[0-9]+$/'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'google_analytics_enable' => 'habilitar Google Analytics',
            'google_analytics_property_id' => 'ID de propiedad',
            'google_analytics_measurement_id' => 'Measurement ID',
            'google_analytics_credentials' => 'credenciales de Google Analytics',
            'analytics_cache_lifetime' => 'tiempo de vida de caché',
            'analytics_reports_daily_enabled' => 'reporte diario',
            'analytics_reports_weekly_enabled' => 'reporte semanal',
            'analytics_reports_monthly_enabled' => 'reporte mensual',
            'analytics_dashboard_widgets' => 'widgets del dashboard',
            'analytics_report_email' => 'Email de reportes',
            'meta_pixel_id' => 'Meta Pixel ID',
            'microsoft_clarity_id' => 'Microsoft Clarity ID',
            'tiktok_pixel_id' => 'TikTok Pixel ID',
            'linkedin_insight_tag_id' => 'LinkedIn Insight Tag ID',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'google_analytics_enable.boolean' => 'El valor para habilitar Google Analytics debe ser verdadero o falso.',
            'google_analytics_property_id.required_if' => 'El ID de propiedad es obligatorio cuando Google Analytics está habilitado.',
            'google_analytics_property_id.regex' => 'El ID de propiedad debe contener solo números.',
            'google_analytics_measurement_id.regex' => 'El Measurement ID debe tener el formato G-XXXXXXXXXX.',
            'google_analytics_credentials.required_if' => 'Las credenciales son obligatorias cuando Google Analytics está habilitado.',
            'analytics_cache_lifetime.min' => 'El tiempo de vida de caché debe ser al menos :min minuto.',
            'analytics_cache_lifetime.max' => 'El tiempo de vida de caché no puede exceder :max minutos (24 horas).',
            'analytics_dashboard_widgets.*.in' => 'El widget seleccionado no es válido.',
            'meta_pixel_id.regex' => 'El Meta Pixel ID debe contener solo números.',
            'microsoft_clarity_id.regex' => 'El Clarity ID debe ser alfanumérico (ej: abcd1234).',
            'tiktok_pixel_id.regex' => 'El TikTok Pixel ID debe ser alfanumérico.',
            'linkedin_insight_tag_id.regex' => 'El LinkedIn Partner ID debe contener solo números.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox values to boolean
        $boolFields = [
            'google_analytics_enable',
            'analytics_reports_daily_enabled',
            'analytics_reports_weekly_enabled',
            'analytics_reports_monthly_enabled',
        ];

        $merge = [];
        foreach ($boolFields as $field) {
            if ($this->has($field)) {
                $merge[$field] = filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN);
            } else {
                $merge[$field] = false;
            }
        }
        $this->merge($merge);

        // Set default cache lifetime if not provided
        if (! $this->has('analytics_cache_lifetime') || empty($this->analytics_cache_lifetime)) {
            $this->merge([
                'analytics_cache_lifetime' => 60, // Default 1 hour
            ]);
        }
    }
}
