<?php

namespace Modules\HelpdeskSla\Tests\Unit;

use Carbon\Carbon;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;
use Modules\HelpdeskSla\Services\ConversationSlaService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * percentUsed() decide cuándo salta el aviso de SLA. Con una conversación que
 * estuvo en snooze, $due ya viene estirado por los minutos pausados, así que el
 * plazo total tiene que descontarlos igual que el tiempo consumido.
 */
class PercentUsedWithPausesTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function percentUsed(Carbon $created, Carbon $due, int $pausedMinutes): int
    {
        $service = new ConversationSlaService(new BusinessHoursCalculator);
        $method = new ReflectionMethod($service, 'percentUsed');

        return $method->invoke($service, $created, $due, false, $pausedMinutes);
    }

    public function test_without_pauses_it_is_elapsed_over_total(): void
    {
        Carbon::setTestNow('2026-09-21 12:00:00');

        $created = Carbon::now()->subMinutes(8);

        $this->assertSame(80, $this->percentUsed($created, $created->copy()->addMinutes(10), 0));
    }

    public function test_pause_is_discounted_from_the_total_window_too(): void
    {
        Carbon::setTestNow('2026-09-21 12:00:00');

        // Política de 10 min con 30 min de pausa: due = created + 40. A los 38 min
        // de vida quedan 8 de 10 min efectivos consumidos (80%), no 8/40 = 20%.
        $created = Carbon::now()->subMinutes(38);
        $due = $created->copy()->addMinutes(40);

        $this->assertSame(80, $this->percentUsed($created, $due, 30));
    }

    public function test_it_reaches_100_exactly_when_the_stretched_due_date_arrives(): void
    {
        Carbon::setTestNow('2026-09-21 12:00:00');

        $created = Carbon::now()->subMinutes(40);
        $due = $created->copy()->addMinutes(40);

        $this->assertSame(100, $this->percentUsed($created, $due, 30));
    }

    public function test_a_degenerate_window_smaller_than_the_pause_counts_as_fully_used(): void
    {
        Carbon::setTestNow('2026-09-21 12:00:00');

        $created = Carbon::now()->subMinutes(20);

        $this->assertSame(100, $this->percentUsed($created, $created->copy()->addMinutes(10), 30));
    }
}
