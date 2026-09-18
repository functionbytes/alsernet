<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskAgents\Models\AgentShift;
use Modules\HelpdeskAgents\Models\AgentVacation;
use Modules\HelpdeskAgents\Models\OncallRotation;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ScheduleControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->createPermissions();

        $this->user = User::factory()->create();
        $this->user->assignRole('super-settings');

        $this->agent = User::factory()->create();
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function createPermissions(): void
    {
        foreach (['helpdesk.schedule.view', 'helpdesk.schedule.update'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('settings.helpdesk.schedule.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->get(route('settings.helpdesk.schedule.index'))
            ->assertForbidden();
    }

    /** @see T0.15-schedule — Form Request must use the same gate as controller middleware */
    public function test_user_without_schedule_update_cannot_store_shift(): void
    {
        $noUpdateUser = User::factory()->create();

        $this->actingAs($noUpdateUser)
            ->post(route('settings.helpdesk.schedule.shifts.store'), [
                'user_id' => $this->agent->id,
                'day_of_week' => 1,
                'start_time' => '09:00',
                'end_time' => '17:00',
            ])
            ->assertForbidden();
    }

    /** @see T0.11 — view namespace must be helpdeskagents:: not helpdesk:: */
    public function test_schedule_index_renders_correct_view(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.helpdesk.schedule.index'))
            ->assertOk()
            ->assertViewIs('helpdeskagents::settings.schedule.index');
    }

    public function test_authorized_user_can_view_schedule_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.helpdesk.schedule.index'))
            ->assertOk();
    }

    public function test_user_can_create_shift(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.helpdesk.schedule.shifts.store'), [
                'user_id' => $this->agent->id,
                'day_of_week' => 1,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'UTC',
            ]);

        $response->assertRedirect(route('settings.helpdesk.schedule.index'));

        $shift = AgentShift::query()->where('user_id', $this->agent->id)->first();
        $this->assertNotNull($shift);
        $this->assertSame(1, $shift->day_of_week);
        $this->assertTrue((bool) $shift->is_active);
    }

    public function test_shift_validation_rejects_end_before_start(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.helpdesk.schedule.shifts.store'), [
                'user_id' => $this->agent->id,
                'day_of_week' => 1,
                'start_time' => '17:00',
                'end_time' => '09:00',
            ])
            ->assertSessionHasErrors('end_time');
    }

    public function test_user_can_destroy_shift(): void
    {
        $shift = AgentShift::create([
            'user_id' => $this->agent->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->delete(route('settings.helpdesk.schedule.shifts.destroy', $shift))
            ->assertRedirect(route('settings.helpdesk.schedule.index'));

        $this->assertSame(0, AgentShift::query()->whereKey($shift->id)->count());
    }

    public function test_user_can_create_vacation(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.helpdesk.schedule.vacations.store'), [
                'user_id' => $this->agent->id,
                'starts_at' => Carbon::tomorrow()->toDateString(),
                'ends_at' => Carbon::tomorrow()->addDays(3)->toDateString(),
                'reason' => 'Vacaciones',
                'status' => 'approved',
            ])
            ->assertRedirect(route('settings.helpdesk.schedule.index'));

        $this->assertSame(1, AgentVacation::query()->where('user_id', $this->agent->id)->count());
    }

    public function test_vacation_validation_rejects_invalid_status(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.helpdesk.schedule.vacations.store'), [
                'user_id' => $this->agent->id,
                'starts_at' => Carbon::tomorrow()->toDateString(),
                'ends_at' => Carbon::tomorrow()->addDay()->toDateString(),
                'status' => 'unknown',
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_user_can_destroy_vacation(): void
    {
        $vacation = AgentVacation::create([
            'user_id' => $this->agent->id,
            'starts_at' => Carbon::today(),
            'ends_at' => Carbon::today()->addDay(),
            'reason' => 'PTO',
            'status' => 'approved',
        ]);

        $this->actingAs($this->user)
            ->delete(route('settings.helpdesk.schedule.vacations.destroy', $vacation))
            ->assertRedirect(route('settings.helpdesk.schedule.index'));

        $this->assertSame(0, AgentVacation::query()->whereKey($vacation->id)->count());
    }

    public function test_user_can_create_oncall_rotation(): void
    {
        $second = User::factory()->create();

        $this->actingAs($this->user)
            ->post(route('settings.helpdesk.schedule.oncall.store'), [
                'name' => 'On-call principal',
                'user_ids' => [$this->agent->id, $second->id],
                'shift_duration_hours' => 24,
                'started_at' => Carbon::now()->toDateTimeString(),
                'current_user_id' => $this->agent->id,
            ])
            ->assertRedirect(route('settings.helpdesk.schedule.index'));

        $rotation = OncallRotation::query()->first();
        $this->assertNotNull($rotation);
        $this->assertSame('On-call principal', $rotation->name);
        $this->assertContains($this->agent->id, $rotation->user_ids);
    }

    public function test_user_can_destroy_oncall_rotation(): void
    {
        $rotation = OncallRotation::create([
            'name' => 'Test',
            'user_ids' => [$this->agent->id],
            'shift_duration_hours' => 8,
            'started_at' => Carbon::now(),
            'current_user_id' => $this->agent->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->delete(route('settings.helpdesk.schedule.oncall.destroy', $rotation))
            ->assertRedirect(route('settings.helpdesk.schedule.index'));

        $this->assertSame(0, OncallRotation::query()->whereKey($rotation->id)->count());
    }

    protected function connectionsToTransact(): array
    {
        return ['mariadb', 'helpdesk'];
    }
}
