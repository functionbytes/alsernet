<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Http\Middleware\Authenticate;
use Modules\Auth\Http\Middleware\CheckSession;
use Modules\Core\Http\Middleware\EnsureModuleIsActive;
use Modules\Core\Http\Middleware\SecurityHeaders;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Page\Http\Middleware\PageCacheMiddleware;
use Modules\Template\Http\Middleware\RegisterTemplateViewPath;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for UserManagement: CRUD, bulk actions, export, search.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $basicRole;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            Authorize::class,
            PermissionMiddleware::class,
            RoleMiddleware::class,
            Authenticate::class,
            CheckSession::class,
            EnsureModuleIsActive::class,
            RegisterTemplateViewPath::class,
            PageCacheMiddleware::class,
            SecurityHeaders::class,
        ]);

        // The Role module's early migration creates a minimal `roles` table
        // without the Spatie Permission columns. Patch it here so tests work.
        $this->ensureRolesTableHasSpatieColumns();

        $this->adminRole = Role::create(['name' => 'administrative', 'guard_name' => 'web']);
        $this->basicRole = Role::create(['name' => 'user', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($this->adminRole);

        // Create permissions required by UserPolicy and grant them to the admin user
        $permissions = ['view-users', 'create-users', 'edit-users', 'delete-users'];
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
        $this->admin->givePermissionTo($permissions);
    }

    /**
     * The Role module migration creates a bare `roles` table (id + timestamps only),
     * which runs before the Spatie Permission migration that checks `hasTable` and skips.
     * This helper adds the missing columns so Spatie Permission works in SQLite tests.
     */
    private function ensureRolesTableHasSpatieColumns(): void
    {
        Schema::table('roles', function ($table) {
            if (! Schema::hasColumn('roles', 'name')) {
                $table->string('name');
            }
            if (! Schema::hasColumn('roles', 'guard_name')) {
                $table->string('guard_name');
            }
        });

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['permission_id', 'model_id', 'model_type'], 'mhp_primary');
            });
        }

        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        // Ensure model_has_roles has the Spatie-expected columns (role_id not id)
        if (Schema::hasTable('model_has_roles') && ! Schema::hasColumn('model_has_roles', 'role_id')) {
            Schema::drop('model_has_roles');
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function ($table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['role_id', 'model_id', 'model_type'], 'mhr_primary');
            });
        }

        app('cache')->store('array')->flush();
    }

    // =========================================================================
    // Index
    // =========================================================================

    public function test_user_list_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.users.index'))
            ->assertOk()
            ->assertViewIs('user::users.index')
            ->assertViewHas('users');
    }

    // =========================================================================
    // Store
    // =========================================================================

    public function test_can_create_user(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.users.store'), [
                'firstname' => 'Nuevo',
                'lastname' => 'Usuario',
                'email' => 'nuevo@example.com',
                'password' => 'password123',
                'available' => '1',
                'role' => $this->basicRole->name,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
    }

    public function test_store_requires_all_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.users.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['firstname', 'lastname', 'email', 'password', 'available', 'role']);
    }

    public function test_email_must_be_unique_on_store(): void
    {
        $existing = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.store'), [
                'firstname' => 'Otro',
                'lastname' => 'Usuario',
                'email' => $existing->email,
                'password' => 'password123',
                'available' => '1',
                'role' => $this->basicRole->name,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // =========================================================================
    // Update
    // =========================================================================

    public function test_can_update_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole($this->basicRole);

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.update'), [
                'uid' => $target->uid,
                'firstname' => 'Nombre actualizado',
                'lastname' => $target->lastname,
                'email' => $target->email,
                'available' => '1',
                'role' => $this->basicRole->name,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'uid' => $target->uid,
            'firstname' => 'Nombre actualizado',
        ]);
    }

    public function test_update_changes_role(): void
    {
        $target = User::factory()->create();
        $target->assignRole($this->basicRole);
        $newRole = Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.update'), [
                'uid' => $target->uid,
                'firstname' => $target->firstname,
                'lastname' => $target->lastname,
                'email' => $target->email,
                'available' => '1',
                'role' => $newRole->name,
            ]);

        $this->assertTrue($target->fresh()->hasRole($newRole->name));
        $this->assertFalse($target->fresh()->hasRole($this->basicRole->name));
    }

    // =========================================================================
    // Destroy
    // =========================================================================

    public function test_admin_can_delete_user(): void
    {
        $target = User::factory()->create();
        $targetUid = $target->uid;

        $this->actingAs($this->admin)
            ->delete(route('settings.users.destroy', ['uid' => $targetUid]))
            ->assertRedirect(route('settings.users.index'));

        $this->assertDatabaseMissing('users', ['uid' => $targetUid]);
    }

    // =========================================================================
    // Bulk actions
    // =========================================================================

    public function test_bulk_activate_users(): void
    {
        $target = User::factory()->create(['available' => false]);
        $target->assignRole($this->basicRole);

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.bulk-action'), [
                'action' => 'activate',
                'ids' => [$target->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'available' => 1]);
    }

    public function test_bulk_deactivate_users(): void
    {
        $target = User::factory()->create(['available' => true]);
        $target->assignRole($this->basicRole);

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.bulk-action'), [
                'action' => 'deactivate',
                'ids' => [$target->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'available' => 0]);
    }

    public function test_bulk_assign_role_to_users(): void
    {
        $target = User::factory()->create();
        $target->assignRole($this->basicRole);
        $newRole = Role::create(['name' => 'manager', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.bulk-action'), [
                'action' => 'assign_role',
                'ids' => [$target->id],
                'value' => $newRole->name,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($target->fresh()->hasRole($newRole->name));
    }

    public function test_bulk_delete_cannot_include_authenticated_user(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.users.bulk-action'), [
                'action' => 'delete',
                'ids' => [$this->admin->id],
            ])
            ->assertOk();

        // The authenticated user must not be deleted
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_bulk_action_validates_action_field(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.bulk-action'), [
                'action' => 'invalid_action',
                'ids' => [$target->id],
            ])
            ->assertUnprocessable();
    }

    public function test_bulk_action_requires_ids(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.users.bulk-action'), [
                'action' => 'activate',
            ])
            ->assertUnprocessable();
    }

    // =========================================================================
    // Export
    // =========================================================================

    public function test_can_export_users_as_csv(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('settings.users.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    // =========================================================================
    // Search
    // =========================================================================

    public function test_search_returns_json_results(): void
    {
        User::factory()->create(['firstname' => 'Carlos', 'lastname' => 'Lopez', 'available' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('settings.users.search', ['q' => 'Carlos']))
            ->assertOk()
            ->assertJsonStructure([['id', 'text', 'first_name', 'last_name', 'email']]);
    }

    public function test_search_returns_empty_array_for_no_matches(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('settings.users.search', ['q' => 'xyznonexistent']))
            ->assertOk()
            ->assertJson([]);
    }
}
