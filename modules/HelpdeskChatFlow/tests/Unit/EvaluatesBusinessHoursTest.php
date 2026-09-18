<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskChatFlow\Services\Concerns\EvaluatesBusinessHours;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;
use Tests\TestCase;

class EvaluatesBusinessHoursTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new class
        {
            use EvaluatesBusinessHours {
                isWithinBusinessHours as public;
            }
        };

        // Calendario de festivos vacío por defecto (equivalente a no tener
        // HelpdeskSla instalado): evita que isHoliday() dispare una consulta
        // real a helpdesk_holidays en cada test que no la necesita.
        Cache::put(BusinessHoursCalculator::HOLIDAYS_CACHE_KEY, ['recurring' => [], 'dates' => []], 300);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::forget(BusinessHoursCalculator::HOLIDAYS_CACHE_KEY);
        parent::tearDown();
    }

    public function test_returns_true_inside_window_on_active_day(): void
    {
        // Wednesday 10:30 UTC
        Carbon::setTestNow(Carbon::parse('2026-06-17 10:30:00', 'UTC'));

        $this->assertTrue($this->subject->isWithinBusinessHours([
            'timezone' => 'UTC',
            'days' => [1, 2, 3, 4, 5],
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]));
    }

    public function test_returns_false_outside_time_window(): void
    {
        // Wednesday 20:00 UTC
        Carbon::setTestNow(Carbon::parse('2026-06-17 20:00:00', 'UTC'));

        $this->assertFalse($this->subject->isWithinBusinessHours([
            'timezone' => 'UTC',
            'days' => [1, 2, 3, 4, 5],
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]));
    }

    public function test_returns_false_on_inactive_day(): void
    {
        // Sunday 10:30 UTC (ISO day 7, not in active list)
        Carbon::setTestNow(Carbon::parse('2026-06-21 10:30:00', 'UTC'));

        $this->assertFalse($this->subject->isWithinBusinessHours([
            'timezone' => 'UTC',
            'days' => [1, 2, 3, 4, 5],
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]));
    }

    public function test_uses_defaults_when_config_missing(): void
    {
        // Monday 12:00 UTC — defaults are Mon-Fri 09:00-18:00
        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', 'UTC'));

        $this->assertTrue($this->subject->isWithinBusinessHours(['timezone' => 'UTC']));
    }

    /** @return array<string, array<string, mixed>> */
    private function overnightWindow(): array
    {
        // Mon-Fri night shift spanning midnight.
        return [
            'timezone' => 'UTC',
            'days' => [1, 2, 3, 4, 5],
            'start_time' => '22:00',
            'end_time' => '06:00',
        ];
    }

    public function test_overnight_window_open_after_start_same_day(): void
    {
        // Monday 23:00 — after the 22:00 start, Monday is active.
        Carbon::setTestNow(Carbon::parse('2026-06-15 23:00:00', 'UTC'));

        $this->assertTrue($this->subject->isWithinBusinessHours($this->overnightWindow()));
    }

    public function test_overnight_window_open_before_dawn_counts_previous_day(): void
    {
        // Saturday 02:00 — pre-dawn stretch of the shift that began Friday (active).
        Carbon::setTestNow(Carbon::parse('2026-06-20 02:00:00', 'UTC'));

        $this->assertTrue($this->subject->isWithinBusinessHours($this->overnightWindow()));
    }

    public function test_overnight_window_closed_before_dawn_when_previous_day_inactive(): void
    {
        // Sunday 02:00 — pre-dawn belongs to Saturday's shift, which is not active.
        Carbon::setTestNow(Carbon::parse('2026-06-21 02:00:00', 'UTC'));

        $this->assertFalse($this->subject->isWithinBusinessHours($this->overnightWindow()));
    }

    public function test_overnight_window_closed_during_the_day(): void
    {
        // Wednesday 12:00 — outside a 22:00-06:00 window.
        Carbon::setTestNow(Carbon::parse('2026-06-17 12:00:00', 'UTC'));

        $this->assertFalse($this->subject->isWithinBusinessHours($this->overnightWindow()));
    }

    public function test_handles_unpadded_hour_in_time_bounds(): void
    {
        // Wednesday 09:05 with an unpadded "9:00" start — lexicographic compare
        // would wrongly read '09:05' < '9:00'; minute conversion gets it right.
        Carbon::setTestNow(Carbon::parse('2026-06-17 09:05:00', 'UTC'));

        $this->assertTrue($this->subject->isWithinBusinessHours([
            'timezone' => 'UTC',
            'days' => [1, 2, 3, 4, 5],
            'start_time' => '9:00',
            'end_time' => '18:00',
        ]));
    }

    public function test_returns_false_on_a_holiday_even_within_the_time_window(): void
    {
        // Wednesday 10:30 UTC — dentro del rango horario normal, pero festivo.
        Carbon::setTestNow(Carbon::parse('2026-06-17 10:30:00', 'UTC'));

        Cache::put(BusinessHoursCalculator::HOLIDAYS_CACHE_KEY, [
            'recurring' => [],
            'dates' => ['2026-06-17' => true],
        ], 300);

        $this->assertFalse($this->subject->isWithinBusinessHours([
            'timezone' => 'UTC',
            'days' => [1, 2, 3, 4, 5],
            'start_time' => '09:00',
            'end_time' => '18:00',
        ]));
    }

    public function test_overnight_window_pre_dawn_checks_the_holiday_of_the_day_the_shift_began(): void
    {
        // Saturday 02:00 — la franja de madrugada pertenece al turno que
        // empezó el viernes: si el viernes es festivo, el turno no cuenta
        // aunque el sábado (día del reloj) no lo sea.
        Carbon::setTestNow(Carbon::parse('2026-06-20 02:00:00', 'UTC'));

        Cache::put(BusinessHoursCalculator::HOLIDAYS_CACHE_KEY, [
            'recurring' => [],
            'dates' => ['2026-06-19' => true],
        ], 300);

        $this->assertFalse($this->subject->isWithinBusinessHours($this->overnightWindow()));
    }
}
