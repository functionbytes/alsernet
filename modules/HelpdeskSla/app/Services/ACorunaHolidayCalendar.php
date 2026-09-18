<?php

namespace Modules\HelpdeskSla\Services;

/**
 * Calendario laboral oficial de A Coruña (Galicia) para el "sincronizar año"
 * del panel de festivos — es la sede real del negocio (ver
 * helpdesk_business_hours.timezone = Europe/Madrid).
 *
 * Curado a mano por año, NO se genera ni se descarga de ninguna API: España
 * no tiene un servicio público estable para esto, y el calendario de cada
 * municipio combina 8 festivos nacionales fijos + festivos "propios" que
 * Galicia y el Concello de A Coruña deciden cada año (y que pueden sustituir
 * a otros cuando caen en domingo — ver comentarios por fecha). Añadir un año
 * nuevo aquí implica verificar la resolución del DOG (Xunta) y del Concello
 * de A Coruña de ese año concreto, no extrapolar el anterior.
 *
 * Fuentes verificadas (septiembre 2026):
 * - 2026: calendariolaboral365.com/calendariolaboral/2026/galicia/a-coruna,
 *   calendarr.com/espana/calendario-laboral-la-coruna, infoleiros.com.
 * - 2027: nota de prensa de la Xunta de Galicia (calendario autonómico, DOG)
 *   + El Español/Quincemil (festivos locales aprobados por el Concello).
 */
class ACorunaHolidayCalendar
{
    /**
     * @return array<int, array{date: string, name: string}>
     */
    public static function forYear(int $year): array
    {
        return match ($year) {
            2026 => [
                ['date' => '2026-01-01', 'name' => 'Año Nuevo'],
                ['date' => '2026-01-06', 'name' => 'Epifanía del Señor (Reyes)'],
                ['date' => '2026-02-17', 'name' => 'Martes de Carnaval (festivo local A Coruña)'],
                ['date' => '2026-03-19', 'name' => 'San José'],
                ['date' => '2026-04-02', 'name' => 'Jueves Santo'],
                ['date' => '2026-04-03', 'name' => 'Viernes Santo'],
                ['date' => '2026-05-01', 'name' => 'Fiesta del Trabajo'],
                ['date' => '2026-06-24', 'name' => 'San Xoán'],
                ['date' => '2026-07-25', 'name' => 'Santiago Apóstol (Día de Galicia)'],
                ['date' => '2026-08-15', 'name' => 'Asunción de la Virgen'],
                ['date' => '2026-10-07', 'name' => 'Virgen del Rosario (festivo local A Coruña)'],
                ['date' => '2026-10-12', 'name' => 'Fiesta Nacional de España'],
                ['date' => '2026-12-08', 'name' => 'Inmaculada Concepción'],
                ['date' => '2026-12-25', 'name' => 'Navidad'],
            ],
            // El 15-ago y el 25-jul de 2027 caen en domingo: Galicia los sustituyó
            // por el 19-mar (San José) y el 17-may (Día das Letras Galegas) —
            // por eso este año NO lleva Asunción ni Santiago Apóstol como festivo
            // separado, a diferencia de 2026.
            2027 => [
                ['date' => '2027-01-01', 'name' => 'Año Nuevo'],
                ['date' => '2027-01-06', 'name' => 'Epifanía del Señor (Reyes)'],
                ['date' => '2027-02-09', 'name' => 'Martes de Carnaval (festivo local A Coruña)'],
                ['date' => '2027-03-19', 'name' => 'San José'],
                ['date' => '2027-03-25', 'name' => 'Jueves Santo'],
                ['date' => '2027-03-26', 'name' => 'Viernes Santo'],
                ['date' => '2027-05-01', 'name' => 'Fiesta del Trabajo'],
                ['date' => '2027-05-17', 'name' => 'Día das Letras Galegas'],
                ['date' => '2027-06-24', 'name' => 'San Xoán (festivo local A Coruña)'],
                ['date' => '2027-10-12', 'name' => 'Fiesta Nacional de España'],
                ['date' => '2027-11-01', 'name' => 'Todos los Santos'],
                ['date' => '2027-12-06', 'name' => 'Día de la Constitución'],
                ['date' => '2027-12-08', 'name' => 'Inmaculada Concepción'],
                ['date' => '2027-12-25', 'name' => 'Navidad'],
            ],
            default => [],
        };
    }

    /**
     * @return array<int, int>
     */
    public static function availableYears(): array
    {
        return [2026, 2027];
    }
}
