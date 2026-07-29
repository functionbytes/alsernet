<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWhatsAppPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.whatsapp-templates.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'marketing' => ['required', 'numeric', 'min:0', 'max:100'],
            'utility' => ['required', 'numeric', 'min:0', 'max:100'],
            'authentication' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'marketing.required' => 'La tarifa de Marketing es obligatoria.',
            'utility.required' => 'La tarifa de Utilidad es obligatoria.',
            'authentication.required' => 'La tarifa de Autenticación es obligatoria.',
            'marketing.numeric' => 'La tarifa de Marketing debe ser un número.',
            'utility.numeric' => 'La tarifa de Utilidad debe ser un número.',
            'authentication.numeric' => 'La tarifa de Autenticación debe ser un número.',
        ];
    }

    public function attributes(): array
    {
        return [
            'marketing' => 'tarifa Marketing',
            'utility' => 'tarifa Utilidad',
            'authentication' => 'tarifa Autenticación',
        ];
    }
}
