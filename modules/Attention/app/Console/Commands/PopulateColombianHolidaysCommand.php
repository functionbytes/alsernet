<?php

namespace Modules\Attention\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Attention\Models\ColombianHoliday;

/**
 * Command para poblar festivos colombianos
 * Calcula festivos fijos, móviles y aquellos que dependen de la fecha de Pascua
 */
class PopulateColombianHolidaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attention:populate-holidays {year? : Año para generar festivos (por defecto año actual)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poblar festivos colombianos para un año específico';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = $this->argument('year') ?? now()->year;

        if (! is_numeric($year) || $year < 2000 || $year > 2100) {
            $this->error('Año inválido. Debe estar entre 2000 y 2100.');

            return Command::FAILURE;
        }

        $year = (int) $year;

        $this->info("Generando festivos colombianos para el año {$year}...");

        // Verificar si ya existen festivos para este año
        if (ColombianHoliday::hasHolidaysForYear($year)) {
            if (! $this->confirm("Ya existen festivos para {$year}. ¿Desea reemplazarlos?")) {
                $this->info('Operación cancelada.');

                return Command::SUCCESS;
            }

            // Eliminar festivos existentes
            ColombianHoliday::whereYear('date', $year)->delete();
            $this->info("Festivos de {$year} eliminados.");
        }

        $holidays = $this->generateHolidays($year);

        // Insertar festivos en la base de datos
        $progressBar = $this->output->createProgressBar(count($holidays));
        $progressBar->start();

        foreach ($holidays as $holiday) {
            ColombianHoliday::create($holiday);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('✓ Se han generado '.count($holidays)." festivos para el año {$year}.");

        // Mostrar tabla con los festivos generados
        $this->table(
            ['Fecha', 'Nombre', 'Tipo', 'Ley Emiliani'],
            collect($holidays)->map(function ($holiday) {
                return [
                    Carbon::parse($holiday['date'])->format('Y-m-d (l)'),
                    $holiday['name'],
                    $holiday['type'] === 'fixed' ? 'Fijo' : 'Móvil',
                    $holiday['is_monday_law'] ? 'Sí' : 'No',
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }

    /**
     * Generar todos los festivos para un año
     */
    protected function generateHolidays(int $year): array
    {
        $holidays = [];

        // Festivos fijos (no se mueven)
        $holidays = array_merge($holidays, $this->getFixedHolidays($year));

        // Festivos móviles (Ley Emiliani - se trasladan al lunes siguiente)
        $holidays = array_merge($holidays, $this->getMondayLawHolidays($year));

        // Festivos que dependen de Pascua
        $holidays = array_merge($holidays, $this->getEasterDependentHolidays($year));

        // Ordenar por fecha
        usort($holidays, fn ($a, $b) => $a['date'] <=> $b['date']);

        return $holidays;
    }

    /**
     * Festivos fijos de Colombia
     */
    protected function getFixedHolidays(int $year): array
    {
        return [
            [
                'date' => Carbon::create($year, 1, 1),
                'name' => 'Año Nuevo',
                'type' => 'fixed',
                'is_monday_law' => false,
            ],
            [
                'date' => Carbon::create($year, 5, 1),
                'name' => 'Día del Trabajo',
                'type' => 'fixed',
                'is_monday_law' => false,
            ],
            [
                'date' => Carbon::create($year, 7, 20),
                'name' => 'Día de la Independencia',
                'type' => 'fixed',
                'is_monday_law' => false,
            ],
            [
                'date' => Carbon::create($year, 8, 7),
                'name' => 'Batalla de Boyacá',
                'type' => 'fixed',
                'is_monday_law' => false,
            ],
            [
                'date' => Carbon::create($year, 12, 8),
                'name' => 'Inmaculada Concepción',
                'type' => 'fixed',
                'is_monday_law' => false,
            ],
            [
                'date' => Carbon::create($year, 12, 25),
                'name' => 'Navidad',
                'type' => 'fixed',
                'is_monday_law' => false,
            ],
        ];
    }

    /**
     * Festivos móviles según Ley Emiliani
     * Se trasladan al lunes siguiente si no caen en lunes
     */
    protected function getMondayLawHolidays(int $year): array
    {
        $holidays = [
            ['month' => 1, 'day' => 6, 'name' => 'Epifanía del Señor (Reyes Magos)'],
            ['month' => 3, 'day' => 19, 'name' => 'San José'],
            ['month' => 6, 'day' => 29, 'name' => 'San Pedro y San Pablo'],
            ['month' => 8, 'day' => 15, 'name' => 'Asunción de la Virgen'],
            ['month' => 10, 'day' => 12, 'name' => 'Día de la Raza'],
            ['month' => 11, 'day' => 1, 'name' => 'Todos los Santos'],
            ['month' => 11, 'day' => 11, 'name' => 'Independencia de Cartagena'],
        ];

        $result = [];

        foreach ($holidays as $holiday) {
            $date = Carbon::create($year, $holiday['month'], $holiday['day']);

            // Si no es lunes, mover al siguiente lunes
            if ($date->dayOfWeek !== Carbon::MONDAY) {
                $date = $date->next(Carbon::MONDAY);
            }

            $result[] = [
                'date' => $date,
                'name' => $holiday['name'],
                'type' => 'movable',
                'is_monday_law' => true,
            ];
        }

        return $result;
    }

    /**
     * Festivos que dependen de la fecha de Pascua
     */
    protected function getEasterDependentHolidays(int $year): array
    {
        $easter = $this->calculateEasterDate($year);

        return [
            [
                'date' => $easter->copy()->subDays(3),
                'name' => 'Jueves Santo',
                'type' => 'movable',
                'is_monday_law' => false,
            ],
            [
                'date' => $easter->copy()->subDays(2),
                'name' => 'Viernes Santo',
                'type' => 'movable',
                'is_monday_law' => false,
            ],
            [
                'date' => $this->moveToNextMonday($easter->copy()->addDays(43)),
                'name' => 'Ascensión del Señor',
                'type' => 'movable',
                'is_monday_law' => true,
            ],
            [
                'date' => $this->moveToNextMonday($easter->copy()->addDays(64)),
                'name' => 'Corpus Christi',
                'type' => 'movable',
                'is_monday_law' => true,
            ],
            [
                'date' => $this->moveToNextMonday($easter->copy()->addDays(71)),
                'name' => 'Sagrado Corazón de Jesús',
                'type' => 'movable',
                'is_monday_law' => true,
            ],
        ];
    }

    /**
     * Calcular la fecha de Pascua usando el algoritmo de Meeus
     */
    protected function calculateEasterDate(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day);
    }

    /**
     * Mover una fecha al siguiente lunes si no cae en lunes
     */
    protected function moveToNextMonday(Carbon $date): Carbon
    {
        if ($date->dayOfWeek !== Carbon::MONDAY) {
            return $date->next(Carbon::MONDAY);
        }

        return $date;
    }
}
