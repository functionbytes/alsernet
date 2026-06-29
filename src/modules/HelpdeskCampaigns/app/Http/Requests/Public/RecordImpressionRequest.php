<?php

namespace Modules\HelpdeskCampaigns\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Public, unauthenticated request — anyone visiting a page where a campaign
 * is rendered can post here. Throttling is set on the route group itself.
 */
class RecordImpressionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'integer', 'min:1'],
            'page_url' => ['nullable', 'string', 'max:2000'],
            'device_type' => ['nullable', 'string', 'in:mobile,tablet,desktop,unknown'],
            'browser' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['nullable', 'integer'],
            'customer_session_id' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:8'],
            'language' => ['nullable', 'string', 'max:8'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'campaign_id.required' => 'El identificador de campaña es obligatorio.',
            'campaign_id.min' => 'El identificador de campaña no es válido.',
            'device_type.in' => 'El tipo de dispositivo no es válido.',
            'country.max' => 'El código de país no puede superar los 8 caracteres.',
            'language.max' => 'El código de idioma no puede superar los 8 caracteres.',
        ];
    }
}
