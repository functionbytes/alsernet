<?php

namespace Modules\Role\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Role\Events\RoleCreated;
use Modules\Role\Events\RoleDeleted;
use Modules\Role\Events\RoleUpdated;
use Modules\Role\Events\UserRoleChanged;
use Modules\Role\Listeners\ClearRolePermissionCacheListener;
use Modules\Role\Listeners\UserRoleChangedListener;
use Modules\Role\Notifications\UserRoleChangedNotification;
use Modules\Role\Tests\TestCase;
use Spatie\Permission\Models\Permission;

class EventsAndListenersTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Event dispatching from RoleController
    // -------------------------------------------------------------------------

    public function test_role_created_event_is_dispatched_on_store(): void
    {
        Event::fake([RoleCreated::class]);
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->postJson(route('settings.roles.store'), [
                'name' => 'rol_evento_creado_'.uniqid(),
                'guard_name' => 'web',
            ]);

        Event::assertDispatched(RoleCreated::class);
    }

    public function test_role_updated_event_is_dispatched_on_update(): void
    {
        Event::fake([RoleUpdated::class]);
        $user = $this->createAdminUser();
        $role = $this->createRole();

        $this->actingAs($user)
            ->putJson(route('settings.roles.update', $role->id), [
                'name' => 'rol_evento_actualizado_'.uniqid(),
                'guard_name' => 'web',
            ]);

        Event::assertDispatched(RoleUpdated::class, fn (RoleUpdated $e) => $e->role->id === $role->id);
    }

    public function test_role_deleted_event_is_dispatched_on_destroy(): void
    {
        Event::fake([RoleDeleted::class]);
        $user = $this->createAdminUser();
        $role = $this->createRole();

        $this->actingAs($user)
            ->deleteJson(route('settings.roles.destroy', $role->id));

        Event::assertDispatched(RoleDeleted::class, fn (RoleDeleted $e) => $e->role->id === $role->id);
    }

    public function test_user_role_changed_event_is_dispatched_on_assign(): void
    {
        Event::fake([UserRoleChanged::class]);
        $user = $this->createAdminUser();
        $role = $this->createRole();
        $target = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('settings.roles.assign.users', $role->id), [
                'user_ids' => [$target->id],
            ]);

        Event::assertDispatched(
            UserRoleChanged::class,
            fn (UserRoleChanged $e) => $e->user->id === $target->id && $e->action === 'assigned'
        );
    }

    public function test_user_role_changed_event_is_dispatched_on_remove(): void
    {
        Event::fake([UserRoleChanged::class]);
        $user = $this->createAdminUser();
        $role = $this->createRole();
        $target = User::factory()->create();
        $target->assignRole($role);

        $this->actingAs($user)
            ->deleteJson(route('settings.roles.remove.user', [$role->id, $target->id]));

        Event::assertDispatched(
            UserRoleChanged::class,
            fn (UserRoleChanged $e) => $e->user->id === $target->id && $e->action === 'removed'
        );
    }

    // -------------------------------------------------------------------------
    // Listener registration
    // -------------------------------------------------------------------------

    public function test_clear_cache_listener_is_registered_for_role_created(): void
    {
        $this->assertTrue(
            Event::hasListeners(RoleCreated::class)
        );
    }

    public function test_clear_cache_listener_is_registered_for_role_updated(): void
    {
        $this->assertTrue(Event::hasListeners(RoleUpdated::class));
    }

    public function test_clear_cache_listener_is_registered_for_role_deleted(): void
    {
        $this->assertTrue(Event::hasListeners(RoleDeleted::class));
    }

    public function test_user_role_changed_listener_is_registered(): void
    {
        $this->assertTrue(Event::hasListeners(UserRoleChanged::class));
    }

    // -------------------------------------------------------------------------
    // ClearRolePermissionCacheListener
    // -------------------------------------------------------------------------

    public function test_clear_cache_listener_flushes_spatie_cache(): void
    {
        $role = $this->createRole();
        $listener = new ClearRolePermissionCacheListener;

        // Create a permission and assign it to seed the cache
        $perm = Permission::firstOrCreate(['name' => 'test.cache.flush', 'guard_name' => 'web']);
        $role->givePermissionTo($perm);

        // Calling handle should not throw and should flush the cache
        $listener->handle(new RoleCreated($role));

        // After flushing, re-querying should still return correct permissions
        $this->assertTrue($role->fresh()->hasPermissionTo('test.cache.flush'));
    }

    // -------------------------------------------------------------------------
    // UserRoleChangedListener
    // -------------------------------------------------------------------------

    public function test_user_role_changed_listener_sends_database_notification(): void
    {
        Notification::fake();

        $role = $this->createRole();
        $target = User::factory()->create();

        $event = new UserRoleChanged($target, $role, 'assigned');
        $listener = new UserRoleChangedListener;
        $listener->handle($event);

        Notification::assertSentTo(
            $target,
            UserRoleChangedNotification::class,
        );
    }

    // -------------------------------------------------------------------------
    // UserRoleChangedNotification
    // -------------------------------------------------------------------------

    public function test_notification_uses_database_channel(): void
    {
        $role = $this->createRole();
        $target = User::factory()->create();

        $notification = new UserRoleChangedNotification($role, 'assigned');

        $this->assertEquals(['database'], $notification->via($target));
    }

    public function test_notification_to_array_contains_required_keys(): void
    {
        $role = $this->createRole(['name' => 'rol_notif_test']);
        $target = User::factory()->create();

        $notification = new UserRoleChangedNotification($role, 'assigned');
        $data = $notification->toArray($target);

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('action_url', $data);
        $this->assertArrayHasKey('role_id', $data);
        $this->assertArrayHasKey('role_name', $data);
        $this->assertEquals('role_changed', $data['type']);
        $this->assertEquals($role->id, $data['role_id']);
    }

    public function test_notification_message_reflects_action(): void
    {
        $role = $this->createRole(['name' => 'rol_accion_test']);
        $target = User::factory()->create();

        $assigned = new UserRoleChangedNotification($role, 'assigned');
        $removed = new UserRoleChangedNotification($role, 'removed');

        $this->assertStringContainsString('asignado', $assigned->toArray($target)['message']);
        $this->assertStringContainsString('removido', $removed->toArray($target)['message']);
    }
}
