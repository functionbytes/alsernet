<?php

namespace Modules\HelpdeskAnalytics\Http\Requests\Managers;

use Carbon\Carbon;
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
     * Rango efectivo, única fuente de verdad para la validación y el controller.
     * `to` es hasta el final de su día (date() resuelve a medianoche y sin
     * endOfDay() se excluía toda la actividad del propio día seleccionado);
     * `from` sin indicar es el inicio del mes de `to`, de modo que un `to` en un
     * mes anterior no produce un rango invertido.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function range(): array
    {
        $to = ($this->date('to') ?? now())->endOfDay();
        $from = $this->date('from') ?? $to->copy()->startOfMonth();

        return [$from, $to];
    }

    /**
     * Tope de 366 días sobre el rango EFECTIVO (el mismo que usa el controller).
     * Sin él, un rango multi-año hace que trends() itere día a día en PHP y que
     * heatmap()/channelDistribution() escaneen toda la tabla de conversaciones
     * sin límite (DoS). Un rango histórico ≤366 días de hace años sigue siendo
     * válido: solo se limita la amplitud, no la edad.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            [$from, $to] = $this->range();

            if ($from->greaterThan($to)) {
                $validator->errors()->add('from', __('helpdeskanalytics::messages.range_inverted'));

                return;
            }

            if ($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) > 366) {
                $validator->errors()->add('to', __('helpdeskanalytics::messages.range_too_wide'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'to.after_or_equal' => __('helpdeskanalytics::messages.range_after_or_equal'),
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => __('helpdeskanalytics::messages.attribute_from'),
            'to' => __('helpdeskanalytics::messages.attribute_to'),
        ];
    }
}
