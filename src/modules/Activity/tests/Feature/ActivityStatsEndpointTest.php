<?php

namespace Modules\Activity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ActivityStatsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = 'Activity.logs.index';

    protected function setUp(): void
    {
        parent::setUp();

        Activity::query()->delete();
        Cache::forget('activity:count-by-event');
    }

    private function createPermission(): Permission
    {
        return Permission::firstOrCreate([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
        ]);
    }

    private function userWithPermission(): User
    {
        $user = User::factory()->create();
        $this->createPermission();
        $user->givePermissionTo(self::PERMISSION);

        return $user;
    }

    public function test_guest_cannot_access_stats(): void
    {
        $this->get(route('activity.logs.stats'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_stats(): void
    {
        $this->createPermission();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('activity.logs.stats'))
            ->assertForbidden();
    }

    public function test_authenticated_user_with_permission_gets_stats_json(): void
    {
        $this->actingAs($this->userWithPermission())
            ->getJson(route('activity.logs.stats'))
            ->assertOk()
            ->assertJsonStructure(['total', 'created', 'updated', 'deleted']);
    }

    public function test_stats_reflect_actual_activity_data(): void
    {
        $user = $this->userWithPermission();
        Activity::query()->delete();
        Cache::forget('activity:count-by-event');

        Activity::forceCreate(['description' => 'created model', 'event' => 'created']);
        Activity::forceCreate(['description' => 'updated model', 'event' => 'updated']);
        Activity::forceCreate(['description' => 'updated model again', 'event' => 'updated']);
        Activity::forceCreate(['description' => 'deleted model', 'event' => 'deleted']);

        $this->actingAs($user)
            ->getJson(route('activity.logs.stats'))
            ->assertOk()
            ->assertJson([
                'total' => 4,
                'created' => 1,
                'updated' => 2,
                'deleted' => 1,
            ]);
    }

    public function test_stats_return_zero_counts_when_no_activity_exists(): void
    {
        $user = $this->userWithPermission();
        Activity::query()->delete();
        Cache::forget('activity:count-by-event');

        $this->actingAs($user)
            ->getJson(route('activity.logs.stats'))
            ->assertOk()
            ->assertJson([
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
            ]);
    }
}
