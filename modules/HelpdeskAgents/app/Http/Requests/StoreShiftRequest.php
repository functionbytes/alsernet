<?php

namespace Modules\HelpdeskAgents\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskAgents\Models\AgentShift;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.schedule.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            // Sin after:start_time — se admiten turnos nocturnos que cruzan
            // medianoche (end_time < start_time), ver AgentAvailabilityService.
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'timezone' => ['nullable', 'string', 'max:64', 'timezone:all'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El agente es obligatorio.',
            'user_id.exists' => 'El agente seleccionado no existe.',
            'day_of_week.required' => 'El dia es obligatorio.',
            'day_of_week.between' => 'El dia debe estar entre 0 (domingo) y 6 (sabado).',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'start_time.date_format' => 'La hora de inicio debe tener formato HH:MM.',
            'end_time.required' => 'La hora de fin es obligatoria.',
            'end_time.date_format' => 'La hora de fin debe tener formato HH:MM.',
            'end_time.different' => 'La hora de fin no puede ser igual a la de inicio.',
            'timezone.timezone' => 'La zona horaria no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'agente',
            'day_of_week' => 'dia de la semana',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
            'timezone' => 'zona horaria',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->overlapsExistingShift()) {
                $validator->errors()->add('start_time', 'Este agente ya tiene un turno que se solapa con ese horario ese dia.');
            }
        });
    }

    /**
     * Whether the requested shift intersects an already-active shift of the
     * same agent on the same day_of_week. Overnight shifts (end < start) are
     * split into their two calendar-day segments before comparing, the same
     * way AgentAvailabilityService evaluates them.
     */
    private function overlapsExistingShift(): bool
    {
        $day = (int) $this->input('day_of_week');
        $newSegments = $this->segmentsFor($day, $this->input('start_time').':00', $this->input('end_time').':00');

        $existingShifts = AgentShift::query()
            ->where('user_id', $this->integer('user_id'))
            ->where('is_active', true)
            ->where(function ($query) use ($day) {
                $query->where('day_of_week', $day)->orWhere('day_of_week', ($day + 6) % 7);
            })
            ->get(['day_of_week', 'start_time', 'end_time']);

        foreach ($existingShifts as $shift) {
            $existingSegments = $this->segmentsFor((int) $shift->day_of_week, $shift->start_time, $shift->end_time);

            foreach ($newSegments as $new) {
                foreach ($existingSegments as $existing) {
                    if ($new['day'] === $existing['day'] && $new['start'] < $existing['end'] && $existing['start'] < $new['end']) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Splits a (day, start, end) shift into 1 or 2 [day, start, end] segments
     * on a per-day clock, so overnight shifts (end < start) can be compared
     * for overlap the same way regardless of which side crosses midnight.
     *
     * @return list<array{day: int, start: string, end: string}>
     */
    private function segmentsFor(int $day, string $start, string $end): array
    {
        if ($end <= $start) {
            return [
                ['day' => $day, 'start' => $start, 'end' => '24:00:00'],
                ['day' => ($day + 1) % 7, 'start' => '00:00:00', 'end' => $end],
            ];
        }

        return [['day' => $day, 'start' => $start, 'end' => $end]];
    }
}
