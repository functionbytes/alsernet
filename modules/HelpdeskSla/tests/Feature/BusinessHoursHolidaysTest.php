<?php

namespace Modules\HelpdeskSla\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskSla\Models\Holiday;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * El calendario de horas hábiles trata los festivos como días no laborables:
 * los vencimientos SLA que caerían en/atravesarían un festivo se empujan al
 * siguiente día hábil.
 */
class BusinessHoursHolidaysTest extends TestCase
{
    use SharesHelpdeskPdo;

    private BusinessHoursCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // Calendario fijo Lun–Vie 09:00–18:00 (evita depender de filas de BD).
        Cache::put(BusinessHoursCalculator::CACHE_KEY, [
            1 => ['open' => '09:00', 'close' => '18:00'],
            2 => ['open' => '09:00', 'close' => '18:00'],
            3 => ['open' => '09:00', 'close' => '18:00'],
            4 => ['open' => '09:00', 'close' => '18:00'],
            5 => ['open' => '09:00', 'close' => '18:00'],
        ], 300);

        $this->calculator = app(BusinessHoursCalculator::class);
    }

    public function test_without_holiday_the_hours_land_the_same_day(): void
    {
        // Miércoles 09:00 + 4h = miércoles 13:00 (control).
        $start = Carbon::parse('2024-01-10 09:00:00', 'Europe/Madrid');

        $result = $this->calculator->addBusinessHours($start, 4);

        $this->assertSame('2024-01-10 13:00', $result->format('Y-m-d H:i'));
    }

    public function test_a_specific_holiday_pushes_the_due_date_to_the_next_day(): void
    {
        Holiday::create(['date' => '2024-01-10', 'name' => 'Festivo puntual', 'is_recurring' => false]);
        Cache::forget(BusinessHoursCalculator::HOLIDAYS_CACHE_KEY);

        // El miércoles es festivo → salta a jueves 09:00 + 4h = jueves 13:00.
        $start = Carbon::parse('2024-01-10 09:00:00', 'Europe/Madrid');

        $result = $this->calculator->addBusinessHours($start, 4);

        $this->assertSame('2024-01-11 13:00', $result->format('Y-m-d H:i'));
    }

    public function test_a_recurring_holiday_is_honoured_every_year(): void
    {
        // Festivo anual fijo 25/12 (recurrente). 2024-12-25 es miércoles.
        Holiday::create(['date' => '2020-12-25', 'name' => 'Navidad', 'is_recurring' => true]);
        Cache::forget(BusinessHoursCalculator::HOLIDAYS_CACHE_KEY);

        $start = Carbon::parse('2024-12-25 09:00:00', 'Europe/Madrid');

        $result = $this->calculator->addBusinessHours($start, 2);

        // Salta el 25 (miércoles festivo) → jueves 26 09:00 + 2h = 11:00.
        $this->assertSame('2024-12-26 11:00', $result->format('Y-m-d H:i'));
    }
}
