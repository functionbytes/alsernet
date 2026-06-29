<?php

namespace Modules\Cache\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CacheSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/panel/settings/cache');

        $response->assertRedirect();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/panel/settings/cache');

        $response->assertForbidden();
    }

    public function test_user_with_permission_can_view_settings(): void
    {
        Permission::firstOrCreate(['name' => 'cache.settings.view', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('cache.settings.view');

        $response = $this->actingAs($user)->get('/panel/settings/cache');

        $response->assertOk();
    }
}
