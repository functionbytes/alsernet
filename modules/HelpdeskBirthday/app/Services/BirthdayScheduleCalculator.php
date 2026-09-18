<?php

namespace Modules\HelpdeskBirthday\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Reparte N correos dentro de una ventana horaria sin pasarse del tope por hora.
 *
 *   interval = max( ventana / N , 3600 / topePorHora )
 *
 * Los dos términos cubren casos distintos:
 *   - ventana/N estira los envíos para ocupar toda la franja (con 20 correos y
 *     5 horas no tiene sentido mandarlos todos en el primer minuto).
 *   - 3600/tope es el suelo duro: por muchos que sean, nunca se supera el ritmo
 *     que aguanta el servidor de correo.
 *
 * Cuando los dos chocan gana el tope, y la campaña termina más tarde que la
 * ventana. Es deliberado: preferimos acabar tarde a que nos bloqueen el envío.
 */
class BirthdayScheduleCalculator
{
    public function plan(
        int $recipients,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd,
        int $throttlePerHour,
    ): BirthdaySchedulePlan {
        if ($windowEnd->lessThanOrEqualTo($windowStart)) {
            throw new InvalidArgumentException(
                'La ventana de envío debe terminar después de empezar.'
            );
        }

        $recipients = max(0, $recipients);
        $windowSeconds = $windowStart->diffInSeconds($windowEnd);

        // Suelo impuesto por el tope por hora. Sin tope (<= 0) no hay suelo.
        $throttleFloor = $throttlePerHour > 0
            ? (int) ceil(3600 / $throttlePerHour)
            : 0;

        // Reparto natural en la ventana. Con 0 o 1 destinatario no hay nada que
        // repartir, así que el intervalo lo fija solo el tope.
        $spread = $recipients > 1
            ? (int) floor($windowSeconds / $recipients)
            : 0;

        // Mínimo 1 segundo entre correos: un intervalo de 0 pondría a todos el
        // mismo scheduled_at y saldrían de golpe, que es justo lo que evitamos.
        $interval = max(1, $spread, $throttleFloor);

        return new BirthdaySchedulePlan(
            recipients: $recipients,
            intervalSeconds: $interval,
            start: $windowStart,
            windowEnd: $windowEnd,
        );
    }

    /**
     * Construye la ventana del día a partir de las horas 'HH:MM' de la config.
     *
     * Las horas se interpretan en la zona de NEGOCIO (helpdeskbirthday.timezone,
     * por defecto Europe/Madrid) y se devuelven en la zona de la app (UTC).
     *
     * Sin esto, "de 9 a 2" se tomaba como 09:00 UTC y los correos salían en
     * España a las 11:00 en horario de verano — la ventana decía una cosa y
     * pasaba otra.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function windowFor(CarbonImmutable $date, string $start, string $end): array
    {
        $businessTz = $this->businessTimezone();
        $appTz = (string) config('app.timezone', 'UTC');

        // El día se ancla en la zona de negocio: a las 23:00 UTC allí ya es
        // mañana, y la campaña del "día siguiente" no debe empezar ayer.
        $localDate = $date->setTimezone($businessTz);

        $windowStart = $this->applyTime($localDate, $start);
        $windowEnd = $this->applyTime($localDate, $end);

        // Ventana nocturna (p.ej. 22:00 → 02:00): el final cae al día siguiente.
        if ($windowEnd->lessThanOrEqualTo($windowStart)) {
            $windowEnd = $windowEnd->addDay();
        }

        return [
            $windowStart->setTimezone($appTz),
            $windowEnd->setTimezone($appTz),
        ];
    }

    /**
     * Hora 'HH:MM' de negocio en formato legible, para el panel.
     */
    public function toBusinessTime(CarbonImmutable $moment): CarbonImmutable
    {
        return $moment->setTimezone($this->businessTimezone());
    }

    public function businessTimezone(): string
    {
        return (string) config('helpdeskbirthday.timezone', config('app.timezone', 'UTC'));
    }

    private function applyTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', trim($time), $m)) {
            throw new InvalidArgumentException("Hora inválida '{$time}', se espera HH:MM.");
        }

        return $date->startOfDay()->setTime((int) $m[1], (int) $m[2]);
    }
}
