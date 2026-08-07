<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessFeaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        // Checkboxes: un toggle desactivado simplemente no viaja en el POST,
        // por eso "sometimes" en vez de "required" — la ausencia se resuelve
        // como false en el controller vía $request->boolean().
        return [
            'business_hours_enabled' => ['sometimes', 'boolean'],
            'off_hours_enabled' => ['sometimes', 'boolean'],
            'greeting_enabled' => ['sometimes', 'boolean'],
            'farewell_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'business_hours_enabled' => 'horarios de atención',
            'off_hours_enabled' => 'fuera de horario',
            'greeting_enabled' => 'bienvenida',
            'farewell_enabled' => 'despedida',
        ];
    }
}
