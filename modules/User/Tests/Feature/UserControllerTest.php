<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
 *
 * Middleware: web, auth, settings
 * The 'settings' middleware = RoleMiddleware with roles:
 *   super-settings|administrative|manager|callcenter|license|accounting|
 *   warehouse|shop|documentation|return
 *
 * NOTE: This test uses DatabaseTransactions (no migration refresh) because
 * the project's SQLite in-memory setup is broken by FULLTEXT index migrations
 * and duplicate Spatie Permission migrations across modules. It requires the
 * MariaDB dev database to be pre-migrated with at minimum: users, roles,
 * model_has_roles, role_has_permissions tables (Spatie Permission).
 */
class UserControllerTest extends TestCase
{
    use DatabaseTransactions;

    private Role $adminRole;

    private Role $basicRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Override DB to use the real MariaDB dev database since in-memory SQLite
        // is broken for this project (FULLTEXT migrations, duplicate tables).
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'inoqualabsystem',
        ]);
        \Illuminate\Support\Facades\DB::purge();
        \Illuminate\Support\Facades\DB::reconnect('mysql');

        $this->ensureTablesExist();

        $this->adminRole = Role::firstOrCreate(['name' => 'administrative', 'guard_name' => 'web']);
        $this->basicRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }

    /**
     * Ensure the minimum required tables exist, running pending migrations
     * only if the users/roles tables are missing.
     */
    private function ensureTablesExist(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles')) {
            $this->artisan('migrate', ['--force' => true]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a user assigned the 'administrative' role (passes 'settings' middleware).
     */
    private function createAdminUser(): User
    {
        $user = User::create([
            'uid' => Str::uuid(),
            'firstname' => 'Admin',
            'lastname' => 'Test',
            'email' => 'pages-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'available' => true,
        ]);

        $user->assignRole($this->adminRole);

        return $user;
    }

    /**
     * Create a plain user with only the basic 'user' role (fails 'settings' middleware).
     */
    private function createBasicUser(): User
    {
        $user = User::create([
            'uid' => Str::uuid(),
            'firstname' => 'Basic',
            'lastname' => 'Test',
            'email' => 'basic-'.Str::random(6).'@example.com',
            'password' => Hash::make('password'),
            'available' => true,
        ]);

        $user->assignRole($this->basicRole);

        return $user;
    }

    // =========================================================================
    // UNAUTHENTICATED ACCESS
    // =========================================================================

    public function test_guest_cannot_access_users_index(): void
    {
        $response = $this->get(route('settings.users.index'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_guest_cannot_access_create_form(): void
    {
        $response = $this->get(route('settings.users.create'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_guest_cannot_submit_store(): void
    {
        $response = $this->post(route('settings.users.store'), []);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_guest_cannot_access_edit_form(): void
    {
        $response = $this->get(route('settings.users.edit', ['uid' => Str::uuid()]));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_guest_cannot_submit_update(): void
    {
        $response = $this->post(route('settings.users.update'), []);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_guest_cannot_delete_user(): void
    {
        $response = $this->delete(route('settings.users.destroy', ['uid' => Str::uuid()]));

        $response->assertRedirect(route('auth.login'));
    }

    // =========================================================================
    // AUTHORIZATION – user without required role
    // =========================================================================

    public function test_user_without_settings_role_cannot_access_index(): void
    {
        $user = $this->createBasicUser();

        $response = $this->actingAs($user)->get(route('settings.users.index'));

        $response->assertForbidden();
    }

    public function test_user_without_settings_role_cannot_access_create(): void
    {
        $user = $this->createBasicUser();

        $response = $this->actingAs($user)->get(route('settings.users.create'));

        $response->assertForbidden();
    }

    public function test_user_without_settings_role_cannot_store(): void
    {
        $user = $this->createBasicUser();

        $response = $this->actingAs($user)->post(route('settings.users.store'), []);

        $response->assertForbidden();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function test_admin_can_view_users_index(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('settings.users.index'));

        $response->assertOk();
        $response->assertViewIs('user::users.index');
        $response->assertViewHas('users');
    }

    // =========================================================================
    // CREATE FORM
    // =========================================================================

    public function test_admin_can_view_create_form(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('settings.users.create'));

        $response->assertOk();
        $response->assertViewIs('user::users.create');
        $response->assertViewHas('roles');
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function test_admin_can_create_user_with_valid_data(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->postJson(route('settings.users.store'), [
            'firstname' => 'John',
            'lastname' => 'Smith',
            'email' => 'john.smith-'.Str::random(6).'@example.com',
            'password' => 'secret123',
            'available' => '1',
            'role' => $this->adminRole->name,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_store_creates_user_in_database(): void
    {
        $admin = $this->createAdminUser();
        $email = 'created-'.Str::random(6).'@example.com';

        $this->actingAs($admin)->postJson(route('settings.users.store'), [
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'email' => $email,
            'password' => 'secret123',
            'available' => '1',
            'role' => $this->adminRole->name,
        ]);

        $this->assertDatabaseHas('users', [
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'email' => $email,
        ]);
    }

    public function test_store_assigns_role_to_new_user(): void
    {
        $admin = $this->createAdminUser();
        $email = 'role-test-'.Str::random(6).'@example.com';

        $this->actingAs($admin)->postJson(route('settings.users.store'), [
            'firstname' => 'Role',
            'lastname' => 'Test',
            'email' => $email,
            'password' => 'secret123',
            'available' => '1',
            'role' => $this->adminRole->name,
        ]);

        $created = User::where('email', $email)->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole($this->adminRole->name));
    }

    public function test_store_sets_mail_verified_at_when_verified_flag_is_set(): void
    {
        $admin = $this->createAdminUser();
        $email = 'verified-'.Str::random(6).'@example.com';

        $this->actingAs($admin)->postJson(route('settings.users.store'), [
            'firstname' => 'Verified',
            'lastname' => 'User',
            'email' => $email,
            'password' => 'secret123',
            'available' => '1',
            'role' => $this->adminRole->name,
            'verified' => '1',
        ]);

        $created = User::where('email', $email)->first();
        $this->assertNotNull($created);
        $this->assertNotNull($created->mail_verified_at);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $admin = $this->createAdminUser();
        $existing = $this->createBasicUser();

        $response = $this->actingAs($admin)->postJson(route('settings.users.store'), [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => $existing->email,
            'password' => 'secret123',
            'available' => '1',
            'role' => $this->adminRole->name,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->postJson(route('settings.users.store'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['firstname', 'lastname', 'email', 'password', 'available', 'role']);
    }

    public function test_store_rejects_invalid_role(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->postJson(route('settings.users.store'), [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john-'.Str::random(6).'@example.com',
            'password' => 'secret123',
            'available' => '1',
            'role' => 'nonexistent-role-xyz',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['role']);
    }

    public function test_store_rejects_password_shorter_than_8_chars(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->postJson(route('settings.users.store'), [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john-'.Str::random(6).'@example.com',
            'password' => 'short',
            'available' => '1',
            'role' => $this->adminRole->name,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    // =========================================================================
    // EDIT FORM
    // =========================================================================

    public function test_admin_can_view_edit_form(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createBasicUser();

        $response = $this->actingAs($admin)->get(route('settings.users.edit', ['uid' => $target->uid]));

        $response->assertOk();
        $response->assertViewIs('user::users.edit');
        $response->assertViewHas('user');
    }

    public function test_edit_returns_404_for_nonexistent_uid(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('settings.users.edit', ['uid' => 'nonexistent-uid']));

        $response->assertNotFound();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_admin_can_update_user(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createBasicUser();

        $response = $this->actingAs($admin)->postJson(route('settings.users.update'), [
            'uid' => $target->uid,
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'email' => $target->email,
            'available' => '1',
            'role' => $this->adminRole->id,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'uid' => $target->uid,
            'firstname' => 'Updated',
            'lastname' => 'Name',
        ]);
    }

    public function test_update_changes_role(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createBasicUser();
        $newRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        $this->actingAs($admin)->postJson(route('settings.users.update'), [
            'uid' => $target->uid,
            'firstname' => $target->firstname,
            'lastname' => $target->lastname,
            'email' => $target->email,
            'available' => '1',
            'role' => $newRole->id,
        ]);

        $this->assertTrue($target->fresh()->hasRole($newRole->name));
    }

    public function test_update_changes_password_when_provided(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createBasicUser();

        $this->actingAs($admin)->postJson(route('settings.users.update'), [
            'uid' => $target->uid,
            'firstname' => $target->firstname,
            'lastname' => $target->lastname,
            'email' => $target->email,
            'available' => '1',
            'role' => $this->adminRole->id,
            'password' => 'newpassword123',
        ]);

        $this->assertTrue(Hash::check('newpassword123', $target->fresh()->password));
    }

    public function test_update_returns_not_found_for_nonexistent_uid(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->postJson(route('settings.users.update'), [
            'uid' => 'nonexistent-uid-xyz',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'available' => '1',
            'role' => $this->adminRole->id,
        ]);

        $response->assertNotFound();
        $response->assertJson(['success' => false]);
    }

    public function test_update_rejects_missing_required_fields(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createBasicUser();

        $response = $this->actingAs($admin)->postJson(route('settings.users.update'), [
            'uid' => $target->uid,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['firstname', 'lastname', 'email', 'available', 'role']);
    }

    public function test_update_allows_same_email_for_same_user(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createBasicUser();

        $response = $this->actingAs($admin)->postJson(route('settings.users.update'), [
            'uid' => $target->uid,
            'firstname' => $target->firstname,
            'lastname' => $target->lastname,
            'email' => $target->email,
            'available' => '1',
            'role' => $this->adminRole->id,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createBasicUser();
        $targetUid = $target->uid;

        $response = $this->actingAs($admin)->delete(route('settings.users.destroy', ['uid' => $targetUid]));

        $response->assertRedirect(route('settings.users.index'));
        $this->assertDatabaseMissing('users', ['uid' => $targetUid]);
    }

    public function test_destroy_returns_404_for_nonexistent_uid(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->delete(route('settings.users.destroy', ['uid' => 'nonexistent-uid-xyz']));

        $response->assertNotFound();
    }

    // =========================================================================
    // VIEW (show)
    // =========================================================================

    public function test_admin_can_view_user_detail(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createBasicUser();

        $response = $this->actingAs($admin)->get(route('settings.users.show', ['uid' => $target->uid]));

        $response->assertOk();
        $response->assertViewIs('user::users.view');
        $response->assertViewHas('user');
    }

    public function test_show_returns_404_for_nonexistent_uid(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('settings.users.show', ['uid' => 'nonexistent-uid-xyz']));

        $response->assertNotFound();
    }
}
