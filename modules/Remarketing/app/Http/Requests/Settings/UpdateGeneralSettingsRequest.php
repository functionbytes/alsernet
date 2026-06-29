<?php

namespace Modules\Remarketing\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.settings.update');
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:30'],
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_email' => ['required', 'email'],
            'tracking_domain' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'provider' => 'proveedor',
            'sender_name' => 'remitente',
            'sender_email' => 'email del remitente',
            'tracking_domain' => 'dominio de tracking',
        ];
    }
}
