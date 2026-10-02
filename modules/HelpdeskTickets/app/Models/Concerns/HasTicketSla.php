<?php

namespace Modules\HelpdeskTickets\Models\Concerns;

use Carbon\Carbon;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Services\SlaService;

trait HasTicketSla
{
    /**
     * Calculate SLA due dates based on policy
     */
    /**
     * @param  bool  $onlyMissing  no pisar los vencimientos que ya vengan
     *                             puestos. Lo usa TicketObserver::creating():
     *                             quien crea un ticket pasando una fecha de SLA
     *                             explícita —una importación, una migración, la
     *                             corrección de un caso concreto— la está
     *                             fijando a propósito, y sobrescribirla dejaba
     *                             el valor pedido en nada sin decir una palabra.
     *                             Los recálculos (cambio de política o de
     *                             prioridad) siguen sobrescribiendo, que es su
     *                             trabajo.
     */
    public function calculateSlaDueDates(bool $persist = true, bool $onlyMissing = false, ?Carbon $from = null): self
    {
        if (! $this->slaPolicy) {
            return $this;
        }

        $policy = $this->slaPolicy;
        // $from: desde cuándo cuentan primera respuesta y resolución. Al
        // recalcular por un cambio de prioridad es el alta del ticket (más lo
        // que estuvo en pausa), no "ahora": subir a urgente un ticket de tres
        // días no puede regalarle un plazo nuevo entero.
        $now = $from ?? Carbon::now();

        // Get priority multiplier
        $priorityMultipliers = $policy->priority_multipliers ?? [
            'urgent' => 0.25,
            'high' => 0.5,
            'normal' => 1.0,
            'low' => 2.0,
        ];
        $multiplier = $priorityMultipliers[$this->priority] ?? 1.0;

        // Calculate first response due date (if not already responded)
        if (! $this->first_response_at && $policy->first_response_time
            && ! ($onlyMissing && $this->sla_first_response_due_at !== null)) {
            $minutes = (int) ($policy->first_response_time * $multiplier);
            $this->sla_first_response_due_at = $this->calculateBusinessTime($now, $minutes, $policy);
        }

        // Siguiente respuesta: solo hay plazo mientras haya una réplica del
        // cliente pendiente de contestar, y lo abre TrackTicketResponseSla al
        // llegar esa réplica. Aquí únicamente se reajusta uno ya abierto
        // (cambio de política o de prioridad). Antes se fijaba al crear el
        // ticket (creación + X) y no se movía nunca más: marcaba
        // incumplimientos falsos en tickets sin réplica alguna.
        if ($policy->next_response_time && $this->sla_next_response_due_at !== null && ! $onlyMissing && $from === null) {
            $minutes = (int) ($policy->next_response_time * $multiplier);
            $this->sla_next_response_due_at = $this->calculateBusinessTime($now, $minutes, $policy);
        }

        // Calculate resolution due date
        if ($policy->resolution_time && ! ($onlyMissing && $this->sla_resolution_due_at !== null)) {
            $minutes = (int) ($policy->resolution_time * $multiplier);
            $this->sla_resolution_due_at = $this->calculateBusinessTime($now, $minutes, $policy);
        }

        // $persist = false para poder calcular desde TicketObserver::creating()
        // y que las fechas entren en el INSERT. Antes esto se llamaba siempre
        // desde created(), así que cada alta de ticket costaba dos escrituras
        // (INSERT + UPDATE) en el camino más caliente del módulo: la ingesta de
        // correo crea un ticket por mensaje entrante.
        if ($persist && $this->exists) {
            $this->saveQuietly();
        }

        return $this;
    }

    /**
     * Un agente respondió al cliente: primera respuesta si faltaba, y el
     * plazo de "siguiente respuesta" queda cumplido. Lo usan tanto los
     * mensajes del hilo (TrackTicketResponseSla) como el editor de correo.
     */
    public function recordAgentResponse(): void
    {
        $changes = ['sla_next_response_due_at' => null, 'last_message_at' => now()];

        if (! $this->first_response_at) {
            $changes['first_response_at'] = now();
        }

        $this->forceFill($changes)->saveQuietly();
    }

    /**
     * Vencimiento de "siguiente respuesta" contando desde $from (la réplica
     * del cliente), con el multiplicador de prioridad y el horario laboral de
     * la política. Null si la política no fija ese plazo.
     */
    public function nextResponseDueFrom(Carbon $from): ?Carbon
    {
        $policy = $this->slaPolicy;

        if (! $policy || ! $policy->next_response_time) {
            return null;
        }

        $multipliers = $policy->priority_multipliers ?? [
            'urgent' => 0.25,
            'high' => 0.5,
            'normal' => 1.0,
            'low' => 2.0,
        ];
        $minutes = (int) ($policy->next_response_time * ($multipliers[$this->priority] ?? 1.0));

        return $this->calculateBusinessTime($from, $minutes, $policy);
    }

    /**
     * Calculate business time (respecting business hours if enabled)
     */
    protected function calculateBusinessTime(Carbon $start, int $minutes, TicketSlaPolicy $policy): Carbon
    {
        if (! $policy->business_hours_only) {
            return $start->copy()->addMinutes($minutes);
        }

        // Parse business hours from policy. ?: y no ??: un horario guardado
        // como [] (la validación solo exige array) no es null, y sin ningún
        // día laborable el bucle de abajo no terminaba nunca.
        $businessHours = $policy->business_hours ?: [
            'monday' => ['start' => '09:00', 'end' => '17:00'],
            'tuesday' => ['start' => '09:00', 'end' => '17:00'],
            'wednesday' => ['start' => '09:00', 'end' => '17:00'],
            'thursday' => ['start' => '09:00', 'end' => '17:00'],
            'friday' => ['start' => '09:00', 'end' => '17:00'],
        ];

        $current = $start->copy()->setTimezone($policy->timezone ?? 'UTC');
        $remainingMinutes = $minutes;

        // Festivos del calendario de negocio (dependencia blanda con HelpdeskSla:
        // sin ese módulo, el cálculo sigue sólo con días de la semana).
        $calculator = class_exists(BusinessHoursCalculator::class)
            ? app(BusinessHoursCalculator::class)
            : null;
        $holidays = $calculator?->holidays() ?? ['recurring' => [], 'dates' => []];

        // Tope de seguridad: si ningún día aporta minutos (inicio = fin en
        // todos, o festivos que lo cubren todo) el bucle giraría para siempre
        // dentro del job o de la petición. Dos años sin hueco laborable es
        // una configuración rota: se cae a tiempo natural.
        $daysScanned = 0;

        while ($remainingMinutes > 0) {
            if (++$daysScanned > 730) {
                return $start->copy()->addMinutes($minutes);
            }

            $dayOfWeek = strtolower($current->format('l'));

            // Skip if not a business day or a holiday
            if (! isset($businessHours[$dayOfWeek]) || ($calculator && $calculator->isHoliday($current, $holidays))) {
                $current->addDay()->setTime(0, 0);

                continue;
            }

            $dayHours = $businessHours[$dayOfWeek];
            [$startHour, $startMinute] = explode(':', $dayHours['start']);
            [$endHour, $endMinute] = explode(':', $dayHours['end']);

            $dayStart = $current->copy()->setTime((int) $startHour, (int) $startMinute);
            $dayEnd = $current->copy()->setTime((int) $endHour, (int) $endMinute);

            // If current time is before business hours, move to start
            if ($current->lessThan($dayStart)) {
                $current = $dayStart->copy();
            }

            // If current time is after business hours, move to next day
            if ($current->greaterThanOrEqualTo($dayEnd)) {
                $current->addDay()->setTime(0, 0);

                continue;
            }

            // Calculate available minutes in this business day
            $availableMinutes = $current->diffInMinutes($dayEnd);

            if ($availableMinutes >= $remainingMinutes) {
                $current->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                $remainingMinutes -= $availableMinutes;
                $current->addDay()->setTime(0, 0);
            }
        }

        // $current vive en la zona de la política; Eloquent guarda la hora de
        // pared del Carbon, así que sin volver a la zona de entrada el
        // vencimiento se desplazaba el offset de la política (mismo cierre
        // que BusinessHoursCalculator::addBusinessMinutes()).
        return $current->setTimezone($start->getTimezone());
    }

    /**
     * Pause SLA timer (when status stops SLA)
     *
     * Fachada sobre SlaService, que es el dueño de la aritmética de SLA. Se
     * mantiene el método porque lo llaman TicketUpdateService y las vistas; la
     * condición del estado (stops_sla_timer) es específica de esta vía y por
     * eso se queda aquí.
     */
    public function pauseSla(): self
    {
        if (! $this->isSlaPaused() && $this->status?->stops_sla_timer) {
            app(SlaService::class)->pauseSla($this);
        }

        return $this;
    }

    /**
     * Resume SLA timer. Ver pauseSla(): la implementación vive en SlaService.
     */
    public function resumeSla(): self
    {
        app(SlaService::class)->resumeSla($this);

        return $this;
    }

    /**
     * Check if SLA is currently paused
     */
    public function isSlaPaused(): bool
    {
        return $this->sla_paused_at !== null;
    }

    /**
     * Check if ticket has SLA breach
     */
    public function hasSlaBreach(): bool
    {
        return $this->sla_first_response_breached
            || $this->sla_next_response_breached
            || $this->sla_resolution_breached;
    }

    /**
     * Determine whether the ticket is currently overdue on SLA resolution.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->sla_resolution_due_at !== null
            && $this->sla_resolution_due_at->isPast()
            && $this->closed_at === null;
    }

    /**
     * SLA status for list badges: on_track, warning, breached, none.
     *
     * Warning fires when less than 25% of the SLA window remains, or the
     * effective due date is under 2 hours away. Uses sla_resolution_breached
     * and sla_resolution_due_at, falling back to the pause-aware effective
     * due date when the timer is paused.
     */
    public function getSlaStatusAttribute(): string
    {
        if ($this->sla_resolution_due_at === null) {
            return 'none';
        }

        if ($this->sla_resolution_breached) {
            return 'breached';
        }

        $due = $this->slaEffectiveDueDate();

        if ($due === null) {
            return 'none';
        }

        $now = Carbon::now();

        if ($due->isPast()) {
            return 'breached';
        }

        $minutesLeft = $now->diffInMinutes($due, false);

        if ($minutesLeft < 120) {
            return 'warning';
        }

        $totalMinutes = $this->created_at?->diffInMinutes($due, false) ?? 0;

        if ($totalMinutes > 0 && ($minutesLeft / $totalMinutes) < 0.25) {
            return 'warning';
        }

        return 'on_track';
    }

    /**
     * Effective SLA resolution due date, accounting for any active pause.
     */
    public function slaEffectiveDueDate(): ?Carbon
    {
        return app(SlaService::class)->getEffectiveDueDate($this);
    }

    /**
     * Get SLA status (ok, warning, breach)
     */
    public function getSlaStatus(): string
    {
        if ($this->hasSlaBreach()) {
            return 'breach';
        }

        $now = Carbon::now();
        $warningThreshold = 30; // 30 minutes before due

        $dueDates = array_filter([
            $this->sla_first_response_due_at,
            $this->sla_next_response_due_at,
            $this->sla_resolution_due_at,
        ]);

        foreach ($dueDates as $dueDate) {
            if ($dueDate && $dueDate->diffInMinutes($now, false) <= $warningThreshold) {
                return 'warning';
            }
        }

        return 'ok';
    }

    /**
     * Categoría cualitativa de urgencia SLA para colorear la fila (ok/warn/
     * breach) — distinta de getSlaStatusAttribute() (on_track/warning/
     * breached/none), que es la usada por el panel lateral; esta es más
     * simple y pensada solo para el punto de color de la lista.
     */
    public function slaRowKind(): string
    {
        if ($this->sla_resolution_breached || $this->sla_first_response_breached) {
            return 'breach';
        }
        $due = $this->sla_resolution_due_at;
        if (! $due) {
            return 'ok';
        }
        $minutes = now()->diffInMinutes($due, false);
        if ($minutes < 0) {
            return 'breach';
        }

        return $minutes < 60 ? 'warn' : 'ok';
    }

    /**
     * Texto corto de SLA para la fila de la lista ("2h 10m", "12m vencido").
     */
    public function slaRowText(): string
    {
        $due = $this->sla_resolution_due_at;
        if (! $due) {
            return $this->resolved_at ? 'resuelto' : '—';
        }
        // (int) es obligatorio aquí: en esta versión de Carbon,
        // diffInMinutes() devuelve float (p. ej. 29.767743866667) en vez de
        // minutos enteros — bug real que expuso el QA con datos de prueba
        // realistas ("29.767743866667m" en la fila del listado).
        $minutes = (int) now()->diffInMinutes($due, false);

        return $minutes < 0
            ? self::humanizeMinutes(abs($minutes)).' vencido'
            : self::humanizeMinutes($minutes);
    }

    /**
     * Minutos → "45m" / "2h 10m" / "3d 6h", el formato del listado.
     *
     * La rama de vencidos no escalaba y escupía el total en minutos crudos
     * ("1842m vencido", visto en el Kanban); ahora las dos direcciones pasan
     * por aquí y usan la misma escala.
     */
    private static function humanizeMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.'m';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours.'h '.($minutes % 60).'m';
        }

        return intdiv($hours, 24).'d '.($hours % 24).'h';
    }
}
