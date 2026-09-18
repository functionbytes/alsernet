<?php

namespace Modules\Helpdesk\Tests\Unit;

use Illuminate\Support\Carbon;
use Modules\Helpdesk\Services\Public\SimulatorContext;
use Tests\TestCase;

class SimulatorContextTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_run_enables_flag_inside_and_restores_outside(): void
    {
        $inside = null;

        SimulatorContext::run(function () use (&$inside) {
            $inside = SimulatorContext::active();
        });

        $this->assertTrue($inside);
        $this->assertFalse(SimulatorContext::active());
    }

    public function test_run_applies_and_restores_simulated_clock(): void
    {
        Carbon::setTestNow();
        $simulated = Carbon::parse('2026-01-01 23:30:00', 'UTC');
        $insideNow = null;

        SimulatorContext::run(function () use (&$insideNow) {
            $insideNow = now()->toIso8601String();
        }, $simulated);

        $this->assertSame($simulated->toIso8601String(), $insideNow);
        $this->assertFalse(Carbon::hasTestNow()); // restored to real clock
    }

    public function test_run_restores_previous_test_now(): void
    {
        $previous = Carbon::parse('2025-05-05 12:00:00', 'UTC');
        Carbon::setTestNow($previous);

        SimulatorContext::run(fn () => null, Carbon::parse('2026-01-01 00:00:00', 'UTC'));

        $this->assertSame($previous->toIso8601String(), now()->toIso8601String());
    }

    public function test_run_without_now_leaves_clock_untouched(): void
    {
        Carbon::setTestNow();

        SimulatorContext::run(fn () => null);

        $this->assertFalse(Carbon::hasTestNow());
    }
}
