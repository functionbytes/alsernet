<?php

namespace Modules\HelpdeskAnalytics\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdeskanalytics.view');
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    /**
     * Tope de 366 días sobre el rango EFECTIVO (con los mismos defaults que
     * AnalyticsController). Sin él, un rango multi-año hace que trends() itere
     * día a día en PHP y que heatmap()/channelDistribution() escaneen toda la
     * tabla de conversaciones sin límite (DoS). Un rango histórico ≤366 días
     * de hace años sigue siendo válido: solo se limita la amplitud, no la edad.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $from = $this->date('from') ?? now()->startOfMonth();
            $to = $this->date('to') ?? now()->endOfMonth();

            if ($from->diffInDays($to) > 366) {
                $validator->errors()->add('to', 'El rango de fechas no puede superar los 366 días.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'to.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => 'fecha inicial',
            'to' => 'fecha final',
        ];
    }
}
