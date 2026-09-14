<?php

namespace Modules\Supplier\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.monitoring.manage');
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'in:openai,anthropic,gemini'],
            'monthly_limit' => ['required', 'numeric', 'min:0'],
            'daily_limit' => ['nullable', 'numeric', 'min:0'],
            'alert_threshold_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'block_on_exceed' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider.required' => 'El proveedor es obligatorio.',
            'provider.in' => 'El proveedor seleccionado no es valido.',
            'monthly_limit.required' => 'El limite mensual es obligatorio.',
            'monthly_limit.numeric' => 'El limite mensual debe ser un numero.',
            'monthly_limit.min' => 'El limite mensual no puede ser negativo.',
            'daily_limit.numeric' => 'El limite diario debe ser un numero.',
            'daily_limit.min' => 'El limite diario no puede ser negativo.',
            'alert_threshold_pct.required' => 'El umbral de alerta es obligatorio.',
            'alert_threshold_pct.min' => 'El umbral minimo es 0%.',
            'alert_threshold_pct.max' => 'El umbral maximo es 100%.',
        ];
    }

    public function attributes(): array
    {
        return [
            'provider' => 'proveedor',
            'monthly_limit' => 'limite mensual',
            'daily_limit' => 'limite diario',
            'alert_threshold_pct' => 'umbral de alerta',
            'is_active' => 'estado activo',
            'block_on_exceed' => 'bloquear al exceder',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'block_on_exceed' => $this->boolean('block_on_exceed'),
        ]);
    }
}
