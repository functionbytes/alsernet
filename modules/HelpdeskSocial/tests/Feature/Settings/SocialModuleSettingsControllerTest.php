<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SocialModuleSettingsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Module::find('HelpdeskSocial')?->isEnabled()) {
            $this->markTestSkipped('HelpdeskSocial module is disabled.');
        }

        // Seed the current (post-migration) permission names.
        Permission::firstOrCreate(['name' => 'helpdesksocial.view', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
    }

    // --- index() ---
    // La pantalla es solo lectura: config('helpdesksocial.*') no tiene un
    // almacén persistente detrás, así que no hay update() que probar (ver
    // SocialModuleSettingsController).

    public function test_user_with_view_permission_can_access_settings_index(): void
    {
        $this->user->givePermissionTo('helpdesksocial.view');

        $this->actingAs($this->user)
            ->get(route('settings.helpdesk.social.index'))
            ->assertOk();
    }

    public function test_unauthenticated_user_is_redirected_from_settings_index(): void
    {
        $this->get(route('settings.helpdesk.social.index'))
            ->assertRedirect();
    }

    public function test_user_without_view_permission_receives_403_on_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.helpdesk.social.index'))
            ->assertForbidden();
    }
}
