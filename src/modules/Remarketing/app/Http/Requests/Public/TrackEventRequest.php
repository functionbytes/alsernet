<?php

namespace Modules\Remarketing\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class TrackEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_token' => ['required', 'string', 'size:64'],
            'type' => ['required', 'string', 'max:60'],
            'properties' => ['nullable'],
            'visitor_id' => ['nullable', 'string', 'max:64'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Acepta `properties` como array (JSON request) o como string (form-encoded
     * con JSON serializado, usado por el pixel JS para evitar CORS preflight).
     */
    protected function prepareForValidation(): void
    {
        $properties = $this->input('properties');

        if (is_string($properties)) {
            $decoded = json_decode($properties, true);
            $this->merge(['properties' => is_array($decoded) ? $decoded : []]);
        }
    }
}
