<?php

namespace Modules\Attention\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Attention\Models\ColombianHoliday;

/**
 * Servicio para el cálculo de días hábiles según Ley 1437/2011 (CPACA)
 * Excluye sábados, domingos y festivos colombianos
 * Usa la base de datos para gestionar festivos dinámicamente
 */
class BusinessDaysService
{
    /**
     * Verifica si una fecha es día hábil
     */
    public static function isBusinessDay(Carbon $date): bool
    {
        // Verificar si es fin de semana
        if ($date->isWeekend()) {
            return false;
        }

        // Verificar si es festivo
        return ! static::isHoliday($date);
    }

    /**
     * Verifica si una fecha es festivo
     * Usa caché para optimizar consultas repetidas
     */
    public static function isHoliday(Carbon $date): bool
    {
        $year = $date->year;
        $cacheKey = "colombian_holidays_{$year}";

        // Obtener festivos del año desde caché o base de datos
        $holidays = Cache::remember($cacheKey, now()->addDay(), function () use ($year) {
            return ColombianHoliday::forYear($year)
                ->pluck('date')
                ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
                ->toArray();
        });

        return in_array($date->format('Y-m-d'), $holidays);
    }

    /**
     * Calcula la diferencia en días hábiles entre dos fechas
     */
    public static function diffInBusinessDays(Carbon $start, Carbon $end): int
    {
        $businessDays = 0;
        $current = $start->copy()->startOfDay();
        $endDate = $end->copy()->startOfDay();

        // Si las fechas son iguales, retornar 0
        if ($current->equalTo($endDate)) {
            return 0;
        }

        // Ajustar dirección si end es anterior a start
        $isForward = $current->lessThan($endDate);
        $increment = $isForward ? 1 : -1;

        while ($isForward ? $current->lessThan($endDate) : $current->greaterThan($endDate)) {
            if (static::isBusinessDay($current)) {
                $businessDays++;
            }
            $current->addDays($increment);
        }

        return $businessDays;
    }

    /**
     * Agrega días hábiles a una fecha
     */
    public static function addBusinessDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();
        $addedDays = 0;

        while ($addedDays < $days) {
            $result->addDay();
            if (static::isBusinessDay($result)) {
                $addedDays++;
            }
        }

        return $result;
    }

    /**
     * Resta días hábiles a una fecha
     */
    public static function subBusinessDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();
        $subtractedDays = 0;

        while ($subtractedDays < $days) {
            $result->subDay();
            if (static::isBusinessDay($result)) {
                $subtractedDays++;
            }
        }

        return $result;
    }

    /**
     * Obtiene el siguiente día hábil
     */
    public static function getNextBusinessDay(Carbon $date): Carbon
    {
        $next = $date->copy()->addDay();

        while (! static::isBusinessDay($next)) {
            $next->addDay();
        }

        return $next;
    }

    /**
     * Obtiene el día hábil anterior
     */
    public static function getPreviousBusinessDay(Carbon $date): Carbon
    {
        $prev = $date->copy()->subDay();

        while (! static::isBusinessDay($prev)) {
            $prev->subDay();
        }

        return $prev;
    }

    /**
     * Obtiene horario de trabajo desde configuración
     */
    public static function getBusinessHours(): array
    {
        return config('attention.business_hours', [
            'start' => '08:00',
            'end' => '18:00',
            'exclude_lunch' => false,
            'lunch_start' => '12:00',
            'lunch_end' => '14:00',
        ]);
    }

    /**
     * Verifica si una hora está dentro del horario laboral
     */
    public static function isBusinessHour(Carbon $datetime): bool
    {
        $hours = static::getBusinessHours();

        $startTime = Carbon::createFromFormat('H:i', $hours['start']);
        $endTime = Carbon::createFromFormat('H:i', $hours['end']);

        $currentTime = Carbon::createFromFormat('H:i', $datetime->format('H:i'));

        $isInRange = $currentTime->between($startTime, $endTime);

        // Si se excluye el almuerzo, verificar que no esté en ese rango
        if ($isInRange && $hours['exclude_lunch']) {
            $lunchStart = Carbon::createFromFormat('H:i', $hours['lunch_start']);
            $lunchEnd = Carbon::createFromFormat('H:i', $hours['lunch_end']);

            if ($currentTime->between($lunchStart, $lunchEnd)) {
                return false;
            }
        }

        return $isInRange;
    }

    /**
     * Calcula minutos hábiles transcurridos entre dos fechas
     * Considera solo horas laborales (8am-6pm por defecto)
     */
    public static function diffInBusinessMinutes(Carbon $start, Carbon $end): int
    {
        $businessMinutes = 0;
        $current = $start->copy();

        $hours = static::getBusinessHours();
        $dailyMinutes = static::getDailyBusinessMinutes($hours);

        // Iterar día por día
        while ($current->lessThan($end)) {
            if (static::isBusinessDay($current)) {
                $dayStart = $current->copy()->setTimeFromTimeString($hours['start']);
                $dayEnd = $current->copy()->setTimeFromTimeString($hours['end']);

                // Ajustar inicio si es el primer día y empieza después del horario
                $periodStart = $current->greaterThan($dayStart) ? $current : $dayStart;

                // Ajustar fin si es el último día o si termina antes del horario
                $periodEnd = $end->lessThan($dayEnd) && $end->isSameDay($current) ? $end : $dayEnd;

                // Calcular minutos de este día
                if ($periodStart->lessThan($periodEnd)) {
                    $dayMinutes = $periodStart->diffInMinutes($periodEnd);

                    // Si se excluye almuerzo, restar esos minutos
                    if ($hours['exclude_lunch']) {
                        $lunchStart = $current->copy()->setTimeFromTimeString($hours['lunch_start']);
                        $lunchEnd = $current->copy()->setTimeFromTimeString($hours['lunch_end']);

                        // Si el periodo incluye el almuerzo, restar esos minutos
                        if ($periodStart->lessThan($lunchEnd) && $periodEnd->greaterThan($lunchStart)) {
                            $lunchMinutes = $lunchStart->diffInMinutes($lunchEnd);
                            $dayMinutes = max(0, $dayMinutes - $lunchMinutes);
                        }
                    }

                    $businessMinutes += $dayMinutes;
                }
            }

            // Avanzar al siguiente día
            $current->addDay()->startOfDay();
        }

        return $businessMinutes;
    }

    /**
     * Obtiene los minutos laborales por día
     */
    protected static function getDailyBusinessMinutes(array $hours): int
    {
        $start = Carbon::createFromFormat('H:i', $hours['start']);
        $end = Carbon::createFromFormat('H:i', $hours['end']);

        $minutes = $start->diffInMinutes($end);

        // Restar almuerzo si está configurado
        if ($hours['exclude_lunch']) {
            $lunchStart = Carbon::createFromFormat('H:i', $hours['lunch_start']);
            $lunchEnd = Carbon::createFromFormat('H:i', $hours['lunch_end']);
            $minutes -= $lunchStart->diffInMinutes($lunchEnd);
        }

        return $minutes;
    }

    /**
     * Obtener todos los días hábiles entre dos fechas (incluyendo ambas)
     */
    public static function getBusinessDaysBetween(Carbon $from, Carbon $to): array
    {
        $businessDays = [];
        $current = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($current->lessThanOrEqualTo($end)) {
            if (static::isBusinessDay($current)) {
                $businessDays[] = $current->copy();
            }
            $current->addDay();
        }

        return $businessDays;
    }

    /**
     * Calcular fecha límite agregando días hábiles
     * Si la fecha inicial no es día hábil, comienza desde el siguiente día hábil
     */
    public static function calculateDeadline(Carbon $date, int $businessDays): Carbon
    {
        $start = $date->copy();

        // Si la fecha inicial no es día hábil, comenzar desde el siguiente día hábil
        if (! static::isBusinessDay($start)) {
            $start = static::getNextBusinessDay($start);
        }

        return static::addBusinessDays($start, $businessDays);
    }
}
