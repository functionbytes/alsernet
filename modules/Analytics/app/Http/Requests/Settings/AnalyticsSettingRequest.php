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
            'google_analytics_credentials' => [
                'nullable',
                'string',
                'required_if:google_analytics_enable,true',
                new AnalyticsCredentialRule,
            ],
            'analytics_cache_lifetime' => [
                'nullable',
                'integer',
                'min:1',
                'max:1440', // Max 24 hours in minutes
            ],
            'analytics_dashboard_widgets' => [
                'nullable',
                'array',
            ],
            'analytics_dashboard_widgets.*' => [
                'string',
                'in:general,top_pages,top_browsers,top_referrers',
            ],
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
            'google_analytics_credentials' => 'credenciales de Google Analytics',
            'analytics_cache_lifetime' => 'tiempo de vida de caché',
            'analytics_dashboard_widgets' => 'widgets del dashboard',
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
            'google_analytics_credentials.required_if' => 'Las credenciales son obligatorias cuando Google Analytics está habilitado.',
            'analytics_cache_lifetime.min' => 'El tiempo de vida de caché debe ser al menos :min minuto.',
            'analytics_cache_lifetime.max' => 'El tiempo de vida de caché no puede exceder :max minutos (24 horas).',
            'analytics_dashboard_widgets.*.in' => 'El widget seleccionado no es válido.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox value to boolean
        if ($this->has('google_analytics_enable')) {
            $this->merge([
                'google_analytics_enable' => filter_var($this->google_analytics_enable, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Set default cache lifetime if not provided
        if (! $this->has('analytics_cache_lifetime') || empty($this->analytics_cache_lifetime)) {
            $this->merge([
                'analytics_cache_lifetime' => 60, // Default 1 hour
            ]);
        }
    }
}
