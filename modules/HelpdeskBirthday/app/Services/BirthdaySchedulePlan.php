<?php

namespace Modules\HelpdeskBirthday\Services;

use Carbon\CarbonImmutable;

/**
 * Resultado del reparto: cada cuánto sale un correo y a qué hora le toca a cada
 * destinatario. Inmutable y sin dependencias, para poder testearlo a pelo.
 */
class BirthdaySchedulePlan
{
    public function __construct(
        public readonly int $recipients,
        public readonly int $intervalSeconds,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $windowEnd,
    ) {}

    /**
     * Hora de envío del destinatario número $index (base 0).
     */
    public function slotFor(int $index): CarbonImmutable
    {
        return $this->start->addSeconds($index * $this->intervalSeconds);
    }

    /**
     * Cuándo saldría el último correo.
     */
    public function estimatedEnd(): CarbonImmutable
    {
        return $this->recipients > 0
            ? $this->slotFor($this->recipients - 1)
            : $this->start;
    }

    /**
     * true si al ritmo permitido no caben todos dentro de la ventana. No es un
     * error: el tope por hora manda, así que la campaña simplemente acaba más
     * tarde. Se avisa en la UI para que nadie se sorprenda.
     */
    public function overflowsWindow(): bool
    {
        return $this->estimatedEnd()->greaterThan($this->windowEnd);
    }

    /**
     * Ritmo efectivo, para mostrarlo en el panel.
     */
    public function perHour(): int
    {
        return $this->intervalSeconds > 0
            ? (int) floor(3600 / $this->intervalSeconds)
            : $this->recipients;
    }
}
