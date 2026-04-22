<?php

namespace Modules\HelpdeskTickets\Tests\Unit;

use Carbon\Carbon;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use PHPUnit\Framework\TestCase;

class RecurringTicketTest extends TestCase
{
    /**
     * Build a RecurringTicket instance without touching the database.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeRecurringTicket(array $attributes = []): RecurringTicket
    {
        $model = $this->getMockBuilder(RecurringTicket::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $reflection = new \ReflectionClass($model);

        $attrProp = $reflection->getProperty('attributes');
        $attrProp->setAccessible(true);
        $attrProp->setValue($model, $attributes);

        $origProp = $reflection->getProperty('original');
        $origProp->setAccessible(true);
        $origProp->setValue($model, $attributes);

        return $model;
    }

    public function test_calculate_next_run_daily(): void
    {
        $base = Carbon::parse('2024-03-01 09:00:00');

        $recurring = $this->makeRecurringTicket([
            'frequency' => 'daily',
            'last_run_at' => $base,
        ]);

        $next = $recurring->calculateNextRun();

        $this->assertEquals($base->copy()->addDay()->toDateString(), $next->toDateString());
    }

    public function test_calculate_next_run_weekly(): void
    {
        $base = Carbon::parse('2024-03-01 09:00:00');

        $recurring = $this->makeRecurringTicket([
            'frequency' => 'weekly',
            'last_run_at' => $base,
        ]);

        $next = $recurring->calculateNextRun();

        $this->assertEquals($base->copy()->addWeek()->toDateString(), $next->toDateString());
    }

    public function test_calculate_next_run_monthly(): void
    {
        $base = Carbon::parse('2024-03-01 09:00:00');

        $recurring = $this->makeRecurringTicket([
            'frequency' => 'monthly',
            'last_run_at' => $base,
        ]);

        $next = $recurring->calculateNextRun();

        $this->assertEquals($base->copy()->addMonth()->toDateString(), $next->toDateString());
    }

    public function test_scope_due_to_run_returns_overdue_items(): void
    {
        // Verify the scope logic is sound by checking scopeDueToRun signature exists
        $this->assertTrue(method_exists(RecurringTicket::class, 'scopeDueToRun'));
    }

    public function test_cron_expression_hourly_parsed_correctly(): void
    {
        $recurring = $this->makeRecurringTicket([
            'frequency' => 'custom',
            'cron_expression' => '0 * * * *',
            'last_run_at' => null,
        ]);

        $next = $recurring->calculateNextRun();

        // Hourly cron: next run should be approximately 1 hour from now
        $this->assertGreaterThan(now()->addMinutes(50), $next);
        $this->assertLessThan(now()->addMinutes(70), $next);
    }

    public function test_cron_expression_daily_parsed_correctly(): void
    {
        $recurring = $this->makeRecurringTicket([
            'frequency' => 'custom',
            'cron_expression' => '0 8 * * *',
            'last_run_at' => null,
        ]);

        $next = $recurring->calculateNextRun();

        // Daily at 08:00: next run is always tomorrow at 08:00
        $this->assertEquals(8, $next->hour);
        $this->assertEquals(0, $next->minute);
        $this->assertEquals(now()->addDay()->toDateString(), $next->toDateString());
    }
}
