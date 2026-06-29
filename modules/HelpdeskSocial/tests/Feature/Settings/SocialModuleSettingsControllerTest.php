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
        Permission::firstOrCreate(['name' => 'helpdesksocial.rules.manage', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
    }

    // --- index() ---

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

    // --- update() ---

    public function test_user_with_rules_manage_permission_can_update_settings(): void
    {
        $this->user->givePermissionTo('helpdesksocial.rules.manage');

        $this->actingAs($this->user)
            ->put(route('settings.helpdesk.social.update'), [
                'intent_provider' => 'hybrid',
            ])
            ->assertRedirect(route('settings.helpdesk.social.index'))
            ->assertSessionHas('success');
    }

    public function test_update_rejects_invalid_intent_provider_with_422(): void
    {
        $this->user->givePermissionTo('helpdesksocial.rules.manage');

        $this->actingAs($this->user)
            ->put(route('settings.helpdesk.social.update'), [
                'intent_provider' => 'invalid-provider',
            ])
            ->assertUnprocessable()
            ->assertSessionHasErrors('intent_provider');
    }

    public function test_user_without_rules_manage_permission_receives_403_on_update(): void
    {
        // User has only helpdesksocial.view — not rules.manage.
        $this->user->givePermissionTo('helpdesksocial.view');

        $this->actingAs($this->user)
            ->put(route('settings.helpdesk.social.update'), [
                'intent_provider' => 'rules',
            ])
            ->assertForbidden();
    }

    public function test_user_with_old_manage_rules_permission_receives_403_on_update(): void
    {
        // Verify the renamed permission: old name helpdesksocial.manage-rules no longer grants access.
        Permission::firstOrCreate(['name' => 'helpdesksocial.manage-rules', 'guard_name' => 'web']);
        $this->user->givePermissionTo('helpdesksocial.manage-rules');

        $this->actingAs($this->user)
            ->put(route('settings.helpdesk.social.update'), [
                'intent_provider' => 'rules',
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_on_update(): void
    {
        $this->put(route('settings.helpdesk.social.update'), [
            'intent_provider' => 'rules',
        ])->assertRedirect();
    }
}
