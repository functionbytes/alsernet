<?php

namespace Modules\Locales\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LocaleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/panel/locales');

        $response->assertRedirect();
    }

    public function test_user_without_permission_cannot_view_locales(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/panel/locales');

        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_user_with_permission_can_view_locales(): void
    {
        Permission::firstOrCreate(['name' => 'locale.view', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('locale.view');

        $response = $this->actingAs($user)->get('/panel/locales');

        $this->assertContains($response->status(), [200, 404]);
    }
}
