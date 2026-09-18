<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskAgents\Models\AgentShift;
use Modules\HelpdeskAgents\Models\AgentVacation;
use Modules\HelpdeskAgents\Services\AgentAvailabilityService;
use Tests\TestCase;

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

        Carbon::setTestNow(Carbon::create(2026, 5, 6, 12, 0, 0)); // Wednesday 12:00 UTC
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

    public function test_user_with_active_shift_now_is_available(): void
    {
        AgentShift::create([
            'user_id' => $this->user->id,
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->assertTrue($this->service->isAgentAvailableNow($this->user->id));
    }

    public function test_user_without_shift_is_unavailable(): void
    {
        $this->assertFalse($this->service->isAgentAvailableNow($this->user->id));
    }

    public function test_user_with_inactive_shift_is_unavailable(): void
    {
        AgentShift::create([
            'user_id' => $this->user->id,
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'timezone' => 'UTC',
            'is_active' => false,
        ]);

        $this->assertFalse($this->service->isAgentAvailableNow($this->user->id));
    }

    public function test_user_outside_shift_window_is_unavailable(): void
    {
        AgentShift::create([
            'user_id' => $this->user->id,
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '20:00:00',
            'end_time' => '23:00:00',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->assertFalse($this->service->isAgentAvailableNow($this->user->id));
    }

    public function test_user_on_approved_vacation_is_unavailable_even_with_active_shift(): void
    {
        AgentShift::create([
            'user_id' => $this->user->id,
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        AgentVacation::create([
            'user_id' => $this->user->id,
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addDay(),
            'reason' => 'PTO',
            'status' => 'approved',
        ]);

        $this->assertFalse($this->service->isAgentAvailableNow($this->user->id));
    }

    public function test_user_on_pending_vacation_is_still_available(): void
    {
        AgentShift::create([
            'user_id' => $this->user->id,
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        AgentVacation::create([
            'user_id' => $this->user->id,
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addDay(),
            'reason' => 'PTO',
            'status' => 'pending',
        ]);

        $this->assertTrue($this->service->isAgentAvailableNow($this->user->id));
    }

    public function test_available_agents_excludes_users_on_vacation(): void
    {
        $available = User::factory()->create();
        $onVacation = User::factory()->create();

        foreach ([$available->id, $onVacation->id] as $userId) {
            AgentShift::create([
                'user_id' => $userId,
                'day_of_week' => Carbon::now()->dayOfWeek,
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'timezone' => 'UTC',
                'is_active' => true,
            ]);
        }

        AgentVacation::create([
            'user_id' => $onVacation->id,
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addDay(),
            'reason' => 'PTO',
            'status' => 'approved',
        ]);

        $ids = $this->service->availableAgents()->pluck('id')->all();

        $this->assertContains($available->id, $ids);
        $this->assertNotContains($onVacation->id, $ids);
    }
}
