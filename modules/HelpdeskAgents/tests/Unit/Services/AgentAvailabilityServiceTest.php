<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Services\AgentAvailabilityService;
use Modules\HelpdeskAgents\Models\AgentShift;
use Modules\HelpdeskAgents\Models\AgentVacation;
use Tests\TestCase;

/**
 * QUAL-01: AgentShift/AgentVacation ya no son un panel desconectado — se
 * integraron en Modules\Helpdesk\Services\AgentAvailabilityService, el
 * servicio que de verdad consumen AssignmentService y AutoAssignmentService.
 * Este test se movio de HelpdeskAgents\Services\AgentAvailabilityService
 * (una clase huerfana sin consumidores reales) a la clase real. Ver
 * modules/HelpdeskAgents/README.md para la decision de arquitectura.
 */
class AgentAvailabilityServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AgentAvailabilityService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Carbon::setTestNow(Carbon::create(2026, 5, 6, 12, 0, 0, 'UTC')); // Wednesday 12:00 UTC
        $this->service = new AgentAvailabilityService;
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();
            DB::connection('helpdesk')->table('helpdesk_agent_shifts')->limit(1)->exists();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function createShift(array $overrides = []): AgentShift
    {
        return AgentShift::create(array_merge([
            'user_id' => $this->user->id,
            'day_of_week' => Carbon::now('UTC')->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'timezone' => 'UTC',
            'is_active' => true,
        ], $overrides));
    }

    public function test_agent_without_any_shift_is_available(): void
    {
        // No shift rows configured at all: never regress setups that never
        // touched the schedule panel.
        $ids = $this->service->filterAvailableAgents([$this->user->id]);

        $this->assertSame([$this->user->id], $ids);
    }

    public function test_agent_with_active_shift_now_is_available(): void
    {
        $this->createShift();

        $ids = $this->service->filterAvailableAgents([$this->user->id]);

        $this->assertSame([$this->user->id], $ids);
    }

    public function test_agent_with_shift_configured_but_outside_window_is_unavailable(): void
    {
        // The agent opted into shift scheduling (has rows), but none of them
        // cover "now" — unlike an agent with zero shifts, this one IS held
        // to the schedule.
        $this->createShift(['start_time' => '20:00:00', 'end_time' => '23:00:00']);

        $ids = $this->service->filterAvailableAgents([$this->user->id]);

        $this->assertSame([], $ids);
    }

    public function test_agent_with_inactive_shift_is_unavailable(): void
    {
        $this->createShift(['is_active' => false]);

        $ids = $this->service->filterAvailableAgents([$this->user->id]);

        $this->assertSame([], $ids);
    }

    public function test_agent_on_approved_vacation_is_unavailable_even_with_active_shift(): void
    {
        $this->createShift();

        AgentVacation::create([
            'user_id' => $this->user->id,
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addDay(),
            'reason' => 'PTO',
            'status' => 'approved',
        ]);

        $ids = $this->service->filterAvailableAgents([$this->user->id]);

        $this->assertSame([], $ids);
    }

    public function test_agent_on_pending_vacation_is_still_available(): void
    {
        $this->createShift();

        AgentVacation::create([
            'user_id' => $this->user->id,
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addDay(),
            'reason' => 'PTO',
            'status' => 'pending',
        ]);

        $ids = $this->service->filterAvailableAgents([$this->user->id]);

        $this->assertSame([$this->user->id], $ids);
    }

    public function test_shift_is_evaluated_in_its_own_timezone_not_utc(): void
    {
        // America/Bogota has a fixed UTC-5 offset (no DST), so the math stays
        // unambiguous: 12:00 UTC == 07:00 Bogota. A shift defined 08:00-16:00
        // in that timezone must NOT be active yet if compared naively in UTC.
        $this->createShift([
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'timezone' => 'America/Bogota',
        ]);

        $this->assertSame([], $this->service->filterAvailableAgents([$this->user->id]));

        // 15:00 UTC == 10:00 Bogota: inside the shift window.
        Carbon::setTestNow(Carbon::create(2026, 5, 6, 15, 0, 0, 'UTC'));

        $this->assertSame([$this->user->id], $this->service->filterAvailableAgents([$this->user->id]));
    }

    public function test_overnight_shift_is_active_before_and_after_midnight(): void
    {
        // Tuesday 22:00 -> Wednesday 06:00 UTC (day_of_week = Tuesday = 2).
        $this->createShift([
            'day_of_week' => 2,
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
        ]);

        // Tuesday 23:00 UTC: evening leg of the shift, same day_of_week.
        Carbon::setTestNow(Carbon::create(2026, 5, 5, 23, 0, 0, 'UTC'));
        $this->assertSame([$this->user->id], $this->service->filterAvailableAgents([$this->user->id]));

        // Wednesday 02:00 UTC: morning leg, already the next calendar day.
        Carbon::setTestNow(Carbon::create(2026, 5, 6, 2, 0, 0, 'UTC'));
        $this->assertSame([$this->user->id], $this->service->filterAvailableAgents([$this->user->id]));

        // Wednesday 12:00 UTC: outside the overnight window entirely.
        Carbon::setTestNow(Carbon::create(2026, 5, 6, 12, 0, 0, 'UTC'));
        $this->assertSame([], $this->service->filterAvailableAgents([$this->user->id]));
    }

    public function test_filters_a_batch_of_agents_with_a_single_query_each(): void
    {
        $onShift = User::factory()->create();
        $offShift = User::factory()->create();
        $noShiftConfigured = User::factory()->create();

        AgentShift::create([
            'user_id' => $onShift->id,
            'day_of_week' => Carbon::now('UTC')->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        AgentShift::create([
            'user_id' => $offShift->id,
            'day_of_week' => Carbon::now('UTC')->dayOfWeek,
            'start_time' => '20:00:00',
            'end_time' => '23:00:00',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $ids = $this->service->filterAvailableAgents([
            $onShift->id, $offShift->id, $noShiftConfigured->id,
        ]);

        $this->assertContains($onShift->id, $ids);
        $this->assertContains($noShiftConfigured->id, $ids);
        $this->assertNotContains($offShift->id, $ids);
    }
}
