<?php

namespace Modules\Notification\Tests\Feature\Settings;

use App\Models\User;
use Modules\Notification\Tests\TestCase;
use Spatie\Permission\Models\Permission;

class NotificationSettingsControllerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createPermissions(): void
    {
        Permission::firstOrCreate(['name' => 'notification.settings.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'notification.settings.update', 'guard_name' => 'web']);
    }

    private function validPayload(): array
    {
        return [
            'cleanup_enabled' => true,
            'cleanup_days' => 30,
            'push_enabled' => false,
            'push_max_retries' => 3,
            'channel_database' => true,
            'channel_mail' => true,
            'channel_push' => false,
            'retention_days' => 60,
        ];
    }

    // -------------------------------------------------------------------------
    // GET settings.notifications.index
    // -------------------------------------------------------------------------

    public function test_index_requires_permission(): void
    {
        $this->createPermissions();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.notifications.index'))
            ->assertForbidden();
    }

    public function test_index_returns_view_with_config(): void
    {
        $this->createPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('notification.settings.view');

        $response = $this->actingAs($user)
            ->get(route('settings.notifications.index'));

        $response->assertOk()
            ->assertViewIs('notification::managers.settings.index')
            ->assertViewHas('config');

        $config = $response->viewData('config');

        $this->assertArrayHasKey('cleanup', $config);
        $this->assertArrayHasKey('push_notifications', $config);
        $this->assertArrayHasKey('channels', $config);
        $this->assertArrayHasKey('retention_days', $config);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('settings.notifications.index'))
            ->assertRedirect(route('auth.login'));
    }

    // -------------------------------------------------------------------------
    // POST settings.notifications.update
    // -------------------------------------------------------------------------

    public function test_update_requires_permission(): void
    {
        $this->createPermissions();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('settings.notifications.update'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_update_requires_authentication(): void
    {
        $this->postJson(route('settings.notifications.update'), $this->validPayload())
            ->assertUnauthorized();
    }

    public function test_update_validates_required_fields(): void
    {
        $this->createPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('notification.settings.update');

        $this->actingAs($user)
            ->postJson(route('settings.notifications.update'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'cleanup_enabled',
                'cleanup_days',
                'push_enabled',
                'push_max_retries',
                'channel_database',
                'channel_mail',
                'channel_push',
                'retention_days',
            ]);
    }

    public function test_update_validates_cleanup_days_range(): void
    {
        $this->createPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('notification.settings.update');

        $payload = array_merge($this->validPayload(), ['cleanup_days' => 0]);

        $this->actingAs($user)
            ->postJson(route('settings.notifications.update'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cleanup_days']);
    }

    public function test_update_validates_retention_days_max(): void
    {
        $this->createPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('notification.settings.update');

        $payload = array_merge($this->validPayload(), ['retention_days' => 400]);

        $this->actingAs($user)
            ->postJson(route('settings.notifications.update'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['retention_days']);
    }

    public function test_update_persists_settings(): void
    {
        $this->createPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('notification.settings.update');

        $this->actingAs($user)
            ->postJson(route('settings.notifications.update'), $this->validPayload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('settings', [
            'key' => 'notification.cleanup.days',
            'value' => '30',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'notification.retention_days',
            'value' => '60',
        ]);
    }

    public function test_update_persists_channel_flags(): void
    {
        $this->createPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo('notification.settings.update');

        $payload = array_merge($this->validPayload(), [
            'channel_database' => true,
            'channel_mail' => false,
            'channel_push' => false,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.notifications.update'), $payload)
            ->assertOk();

        $this->assertDatabaseHas('settings', [
            'key' => 'notification.channels.database',
            'value' => '1',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'notification.channels.mail',
            'value' => '0',
        ]);
    }
}
