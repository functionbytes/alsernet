<?php

namespace Modules\HelpdeskBirthday\Services;

use Carbon\CarbonImmutable;

/**
 * Qué días 'MM-DD' hay que consultar al ERP para una fecha dada.
 *
 * Normalmente es uno solo. La excepción es quien nació un 29 de febrero: en
 * años no bisiestos ese día no existe, así que se le felicita el 28-feb o el
 * 1-mar según la política configurada, y ese día se consultan dos fechas.
 */
class BirthdayDayResolver
{
    public const POLICY_FEB_28 = 'feb28';

    public const POLICY_MAR_01 = 'mar01';

    /**
     * @return array<int, string> días en formato 'MM-DD'
     */
    public function daysFor(CarbonImmutable $date): array
    {
        $days = [$date->format('m-d')];

        if ($date->isLeapYear()) {
            // El 29-feb existe este año: cada uno cobra su día, sin trasvases.
            return $days;
        }

        $policy = (string) config('helpdeskbirthday.leap_day_policy', self::POLICY_FEB_28);

        $carryDay = $policy === self::POLICY_MAR_01 ? '03-01' : '02-28';

        if ($days[0] === $carryDay) {
            $days[] = '02-29';
        }

        return $days;
    }
}
