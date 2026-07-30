<?php

namespace Modules\HelpdeskTickets\Tests\Unit;

use Carbon\Carbon;
use Modules\HelpdeskTickets\Services\SlaService;
use PHPUnit\Framework\TestCase;

class SlaBusinessHoursTest extends TestCase
{
    // Business hours: Mon–Fri 09:00–18:00 (9 hours per day)

    private function makeSlaService(): SlaService
    {

        return new SlaService;
    }

    private function callAddBusinessHours(Carbon $start, int $hours): Carbon
    {
        $service = $this->makeSlaService();
        $reflection = new \ReflectionClass(SlaService::class);
        $method = $reflection->getMethod('addBusinessHours');
        $method->setAccessible(true);

        return $method->invoke($service, $start, $hours);
    }

    // ─── Weekday within same day ────────────────────────────────────────────────

    public function test_adds_hours_within_same_business_day(): void
    {
        // Monday 09:00 + 4h = Monday 13:00
        $monday9am = Carbon::parse('2024-01-08 09:00:00');
        $result = $this->callAddBusinessHours($monday9am, 4);

        $this->assertEquals('2024-01-08 13:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_adds_zero_hours_returns_same_time(): void
    {
        $monday10am = Carbon::parse('2024-01-08 10:00:00');
        $result = $this->callAddBusinessHours($monday10am, 0);

        $this->assertEquals('2024-01-08 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_exactly_fills_remaining_hours_today(): void
    {
        // Monday 15:00, 3h available (15→18) + 0 remaining = Monday 18:00
        $monday3pm = Carbon::parse('2024-01-08 15:00:00');
        $result = $this->callAddBusinessHours($monday3pm, 3);

        $this->assertEquals('2024-01-08 18:00:00', $result->format('Y-m-d H:i:s'));
    }

    // ─── Spanning to next business day ──────────────────────────────────────────

    public function test_spans_to_next_day_when_hours_exceed_today(): void
    {
        // Monday 16:00: 2h available today → uses 2h, 2h remain → Tuesday 09:00 + 2h = 11:00
        $monday4pm = Carbon::parse('2024-01-08 16:00:00');
        $result = $this->callAddBusinessHours($monday4pm, 4);

        $this->assertEquals('2024-01-09 11:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_spans_multiple_days(): void
    {
        // Monday 09:00 + 20h: 9h today (→Mon 18), 9h Tue (→Tue 18), 2h Wed (→Wed 11)
        $monday9am = Carbon::parse('2024-01-08 09:00:00');
        $result = $this->callAddBusinessHours($monday9am, 20);

        $this->assertEquals('2024-01-10 11:00:00', $result->format('Y-m-d H:i:s'));
    }

    // ─── Weekend skipping ───────────────────────────────────────────────────────

    public function test_skips_weekend_when_spanning_from_friday(): void
    {
        // Friday 16:00: 2h available → uses 2h, 2h remain
        // Skip Sat/Sun → Monday 09:00 + 2h = Monday 11:00
        $friday4pm = Carbon::parse('2024-01-12 16:00:00');
        $result = $this->callAddBusinessHours($friday4pm, 4);

        $this->assertEquals('2024-01-15 11:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_weekend_start_advances_to_monday_9am(): void
    {
        // Saturday 10:00 → advance to Monday 09:00, then add 2h = Monday 11:00
        $saturday = Carbon::parse('2024-01-13 10:00:00');
        $result = $this->callAddBusinessHours($saturday, 2);

        $this->assertEquals('2024-01-15 11:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_sunday_start_advances_to_monday_9am(): void
    {
        // Sunday 14:00 → advance to Monday 09:00, then add 1h = Monday 10:00
        $sunday = Carbon::parse('2024-01-14 14:00:00');
        $result = $this->callAddBusinessHours($sunday, 1);

        $this->assertEquals('2024-01-15 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    // ─── Edge cases around business hours boundaries ────────────────────────────

    public function test_before_business_hours_snaps_to_9am_then_adds(): void
    {
        // Monday 07:00 → snapped to 09:00 → add 1h = 10:00
        $monday7am = Carbon::parse('2024-01-08 07:00:00');
        $result = $this->callAddBusinessHours($monday7am, 1);

        $this->assertEquals('2024-01-08 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_at_end_of_business_day_carries_to_next(): void
    {
        // Monday 18:00 → after hours → advance to Tuesday 09:00, add 1h = 10:00
        $monday6pm = Carbon::parse('2024-01-08 18:00:00');
        $result = $this->callAddBusinessHours($monday6pm, 1);

        $this->assertEquals('2024-01-09 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_after_business_hours_carries_to_next_day(): void
    {
        // Monday 20:00 → advance to Tuesday 09:00, add 2h = 11:00
        $monday8pm = Carbon::parse('2024-01-08 20:00:00');
        $result = $this->callAddBusinessHours($monday8pm, 2);

        $this->assertEquals('2024-01-09 11:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_friday_after_hours_advances_to_monday(): void
    {
        // Friday 19:00 → advance to Monday 09:00, add 1h = Monday 10:00
        $friday7pm = Carbon::parse('2024-01-12 19:00:00');
        $result = $this->callAddBusinessHours($friday7pm, 1);

        $this->assertEquals('2024-01-15 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    // ─── Full business day ──────────────────────────────────────────────────────

    public function test_exactly_one_full_business_day(): void
    {
        // Monday 09:00 + 9h (full day) = Monday 18:00
        $monday9am = Carbon::parse('2024-01-08 09:00:00');
        $result = $this->callAddBusinessHours($monday9am, 9);

        $this->assertEquals('2024-01-08 18:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_one_more_than_full_business_day(): void
    {
        // Monday 09:00 + 10h → Mon 18:00 (9h used), 1h remain → Tue 09:00 + 1h = 10:00
        $monday9am = Carbon::parse('2024-01-08 09:00:00');
        $result = $this->callAddBusinessHours($monday9am, 10);

        $this->assertEquals('2024-01-09 10:00:00', $result->format('Y-m-d H:i:s'));
    }
}
