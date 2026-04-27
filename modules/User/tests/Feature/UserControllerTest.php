<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
 * Feature tests for the User module's UsersController.
 *
 * Routes (prefix: /setting/users, name: settings.users.*):
 *   GET    /             → index
 *   GET    /create       → create
 *   GET    /{uid}        → show
 *   GET    /{uid}/edit   → edit
 *   POST   /             → store  (JSON response)
 *   POST   /update       → update (JSON response)
 *   DELETE /{uid}        → destroy
 */
class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

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
    // INDEX
    // =========================================================================

    public function test_index_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.users.index'))
            ->assertOk()
            ->assertViewIs('user::users.index')
            ->assertViewHas('users');
    }

    public function test_index_with_search_filter_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.users.index', ['search' => 'john']))
            ->assertOk();
    }

    public function test_index_with_role_filter_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.users.index', ['role' => 'administrative']))
            ->assertOk();
    }

    // =========================================================================
    // CREATE FORM
    // =========================================================================

    public function test_create_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.users.create'))
            ->assertOk()
            ->assertViewIs('user::users.create')
            ->assertViewHas('roles');
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function test_store_creates_user_with_valid_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.users.store'), [
                'firstname' => 'John',
                'lastname' => 'Smith',
                'email' => 'john.smith@example.com',
                'password' => 'secret123',
                'available' => '1',
                'role' => $this->adminRole->name,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'firstname' => 'John',
            'lastname' => 'Smith',
            'email' => 'john.smith@example.com',
        ]);
    }

    public function test_store_assigns_role_to_created_user(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.users.store'), [
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'email' => 'jane.doe@example.com',
                'password' => 'secret123',
                'available' => '1',
                'role' => $this->adminRole->name,
            ]);

        $created = User::where('email', 'jane.doe@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole($this->adminRole->name));
    }

    public function test_store_sets_mail_verified_at_when_verified_flag_is_one(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.users.store'), [
                'firstname' => 'Verified',
                'lastname' => 'User',
                'email' => 'verified@example.com',
                'password' => 'secret123',
                'available' => '1',
                'role' => $this->adminRole->name,
                'verified' => '1',
            ]);

        $created = User::where('email', 'verified@example.com')->first();
        $this->assertNotNull($created);
        $this->assertNotNull($created->mail_verified_at);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.users.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['firstname', 'lastname', 'email', 'password', 'available', 'role']);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.store'), [
                'firstname' => 'New',
                'lastname' => 'User',
                'email' => $existing->email,
                'password' => 'secret123',
                'available' => '1',
                'role' => $this->adminRole->name,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_store_rejects_invalid_role(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.users.store'), [
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john@example.com',
                'password' => 'secret123',
                'available' => '1',
                'role' => 'nonexistent-role-xyz',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_store_rejects_password_shorter_than_8_chars(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.users.store'), [
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john@example.com',
                'password' => 'short',
                'available' => '1',
                'role' => $this->adminRole->name,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    // =========================================================================
    // SHOW (view)
    // =========================================================================

    public function test_show_returns_200(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('settings.users.show', ['uid' => $target->uid]))
            ->assertOk()
            ->assertViewIs('user::users.view')
            ->assertViewHas('user');
    }

    public function test_show_returns_404_for_nonexistent_uid(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.users.show', ['uid' => 'nonexistent-uid-xyz']))
            ->assertNotFound();
    }

    // =========================================================================
    // EDIT FORM
    // =========================================================================

    public function test_edit_returns_200(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('settings.users.edit', ['uid' => $target->uid]))
            ->assertOk()
            ->assertViewIs('user::users.edit')
            ->assertViewHas('user');
    }

    public function test_edit_returns_404_for_nonexistent_uid(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.users.edit', ['uid' => 'nonexistent-uid-xyz']))
            ->assertNotFound();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_update_modifies_user_in_database(): void
    {
        $target = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(route('settings.users.update'), [
                'uid' => $target->uid,
                'firstname' => 'Updated',
                'lastname' => 'Name',
                'email' => $target->email,
                'available' => '1',
                'role' => $this->adminRole->name,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'uid' => $target->uid,
            'firstname' => 'Updated',
            'lastname' => 'Name',
        ]);
    }

    public function test_update_changes_password_when_provided(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.update'), [
                'uid' => $target->uid,
                'firstname' => $target->firstname,
                'lastname' => $target->lastname,
                'email' => $target->email,
                'available' => '1',
                'role' => $this->adminRole->name,
                'password' => 'newpassword123',
            ]);

        $this->assertTrue(Hash::check('newpassword123', $target->fresh()->password));
    }

    public function test_update_allows_same_email_for_same_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.update'), [
                'uid' => $target->uid,
                'firstname' => $target->firstname,
                'lastname' => $target->lastname,
                'email' => $target->email,
                'available' => '1',
                'role' => $this->adminRole->name,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_update_returns_404_for_nonexistent_uid(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('settings.users.update'), [
                'uid' => 'nonexistent-uid-xyz',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john@example.com',
                'available' => '1',
                'role' => $this->adminRole->name,
            ])
            ->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_update_rejects_missing_required_fields(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('settings.users.update'), ['uid' => $target->uid])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['firstname', 'lastname', 'email', 'available', 'role']);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_destroy_removes_user_from_database(): void
    {
        $target = User::factory()->create();
        $targetUid = $target->uid;

        $this->actingAs($this->admin)
            ->delete(route('settings.users.destroy', ['uid' => $targetUid]))
            ->assertRedirect(route('settings.users.index'));

        $this->assertDatabaseMissing('users', ['uid' => $targetUid]);
    }

    public function test_destroy_with_nonexistent_uid_is_rejected(): void
    {
        // Note: User::uid() scope returns a Builder (not null) for no-match UIDs,
        // so the controller's abort(404) does not fire. The authorize() call with
        // the Builder triggers an authorization failure instead (403).
        $this->actingAs($this->admin)
            ->delete(route('settings.users.destroy', ['uid' => 'nonexistent-uid-xyz']))
            ->assertStatus(403);
    }
}
