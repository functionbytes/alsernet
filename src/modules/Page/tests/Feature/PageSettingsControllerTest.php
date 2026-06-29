<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'page.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'page.update', 'guard_name' => 'web']);
    }

    public function test_guest_cannot_access_settings_page(): void
    {
        $this->get(route('settings.pages'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_page_view_permission_cannot_access_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.pages'))
            ->assertForbidden();
    }

    public function test_user_with_page_view_permission_can_access_settings(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('page.view');

        $this->actingAs($user)
            ->get(route('settings.pages'))
            ->assertOk()
            ->assertViewIs('page::pages.settings');
    }

    public function test_user_without_page_update_permission_cannot_update_settings(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('page.view');

        $this->actingAs($user)
            ->put(route('settings.pages.update'), [
                'permalink_prefix' => 'blog',
            ])
            ->assertForbidden();
    }

    public function test_user_with_page_update_permission_can_update_settings(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['page.view', 'page.update']);

        $this->actingAs($user)
            ->put(route('settings.pages.update'), [
                'permalink_prefix' => 'blog',
                'homepage_page_id' => null,
                'supported_locales' => ['es', 'en'],
            ])
            ->assertRedirect(route('settings.pages'))
            ->assertSessionHas('success');
    }
}
