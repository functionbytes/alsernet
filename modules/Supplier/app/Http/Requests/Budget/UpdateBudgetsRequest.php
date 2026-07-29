<?php

namespace Modules\Supplier\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.monitoring.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'budgets'                           => ['required', 'array'],
            'budgets.*.monthly_limit'           => ['required', 'numeric', 'min:0'],
            'budgets.*.daily_limit'             => ['nullable', 'numeric', 'min:0'],
            'budgets.*.alert_threshold_pct'     => ['required', 'numeric', 'min:0', 'max:100'],
            'budgets.*.is_active'               => ['boolean'],
            'budgets.*.block_on_exceed'         => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'budgets.*.monthly_limit.required'      => 'El límite mensual es obligatorio.',
            'budgets.*.monthly_limit.numeric'        => 'El límite mensual debe ser un número.',
            'budgets.*.monthly_limit.min'            => 'El límite mensual no puede ser negativo.',
            'budgets.*.daily_limit.numeric'          => 'El límite diario debe ser un número.',
            'budgets.*.daily_limit.min'              => 'El límite diario no puede ser negativo.',
            'budgets.*.alert_threshold_pct.required' => 'El umbral de alerta es obligatorio.',
            'budgets.*.alert_threshold_pct.min'      => 'El umbral mínimo es 0%.',
            'budgets.*.alert_threshold_pct.max'      => 'El umbral máximo es 100%.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $budgets = collect($this->input('budgets', []))
            ->only(['openai', 'anthropic'])
            ->map(function ($data) {
                $data['is_active']       = isset($data['is_active']) && (bool) $data['is_active'];
                $data['block_on_exceed'] = isset($data['block_on_exceed']) && (bool) $data['block_on_exceed'];

                return $data;
            })
            ->all();

        $this->merge(['budgets' => $budgets]);
    }
}
