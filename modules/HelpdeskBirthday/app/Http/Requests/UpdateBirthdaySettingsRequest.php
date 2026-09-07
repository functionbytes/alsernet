<?php

namespace Modules\HelpdeskBirthday\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskBirthday\Services\BirthdayDayResolver;

class UpdateBirthdaySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskbirthday.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'window_start' => ['required', 'date_format:H:i'],
            'window_end' => ['required', 'date_format:H:i'],
            'throttle_per_hour' => ['required', 'integer', 'min:1', 'max:100000'],
            'max_recipients' => ['required', 'integer', 'min:1', 'max:1000000'],
            'leap_day_policy' => ['required', 'in:'.BirthdayDayResolver::POLICY_FEB_28.','.BirthdayDayResolver::POLICY_MAR_01],
            'template_key' => ['required', 'string', 'max:190'],
            'audience_source' => ['required', 'in:api,gestion'],

            // 0 = sin configurar; el generador se niega a llamar al ERP así, y
            // sin bono no se envía ninguna felicitación.
            'bono_type_id' => ['nullable', 'integer', 'min:0'],

            'commercial_optin' => ['required', 'boolean'],
            'lopd_accepted' => ['required', 'boolean'],
            'has_email' => ['required', 'boolean'],
            'check_suppressions' => ['required', 'boolean'],
        ];
    }

    /**
     * Valores ya normalizados para BirthdaySettings::save().
     *
     * Los booleanos se leen con boolean() y no con has(): llegan del formulario
     * como select '0'/'1', y has() daría true incluso para '0'.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $validated = $this->validated();

        foreach (['commercial_optin', 'lopd_accepted', 'has_email', 'check_suppressions'] as $flag) {
            $validated[$flag] = $this->boolean($flag);
        }

        return $validated;
    }
}
