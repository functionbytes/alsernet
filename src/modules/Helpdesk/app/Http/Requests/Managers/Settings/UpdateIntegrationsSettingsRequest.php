<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\IntegrationsController;

class UpdateIntegrationsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return collect(IntegrationsController::TOGGLEABLE_MODULES)
            ->mapWithKeys(fn (array $module): array => [
                "{$module['key']}_integration_enabled" => ['nullable', 'in:0,1'],
            ])
            ->all();
    }

    public function messages(): array
    {
        return collect(IntegrationsController::TOGGLEABLE_MODULES)
            ->mapWithKeys(fn (array $module): array => [
                "{$module['key']}_integration_enabled.in" => 'El valor del interruptor no es válido.',
            ])
            ->all();
    }

    public function attributes(): array
    {
        return collect(IntegrationsController::TOGGLEABLE_MODULES)
            ->mapWithKeys(fn (array $module, string $name): array => [
                "{$module['key']}_integration_enabled" => "integración {$name}",
            ])
            ->all();
    }
}
