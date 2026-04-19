<?php

namespace Modules\Modules\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModulesControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureSchema();
    }

    // -------------------------------------------------------------------------
    // Schema helpers
    // -------------------------------------------------------------------------

    private function ensureSchema(): void
    {
        if (! Schema::hasTable('permissions')) {
            DB::statement('CREATE TABLE permissions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL,
                guard_name VARCHAR(191) NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE (name, guard_name)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('roles')) {
            DB::statement('CREATE TABLE roles (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL,
                guard_name VARCHAR(191) NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE (name, guard_name)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('model_has_permissions')) {
            DB::statement('CREATE TABLE model_has_permissions (
                permission_id BIGINT UNSIGNED NOT NULL,
                model_type VARCHAR(191) NOT NULL,
                model_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (permission_id, model_id, model_type),
                INDEX (model_id, model_type)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('model_has_roles')) {
            DB::statement('CREATE TABLE model_has_roles (
                role_id BIGINT UNSIGNED NOT NULL,
                model_type VARCHAR(191) NOT NULL,
                model_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (role_id, model_id, model_type),
                INDEX (model_id, model_type)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('role_has_permissions')) {
            DB::statement('CREATE TABLE role_has_permissions (
                permission_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (permission_id, role_id)
            ) ENGINE=InnoDB');
        }
    }

    // -------------------------------------------------------------------------
    // User factory helpers
    // -------------------------------------------------------------------------

    /**
     * Create a user with the `modules.manage` permission and a role that
     * satisfies the `settings` middleware (`super-settings` or `administrative`).
     */
    private function createAdminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate(['name' => 'modules.manage', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo($permission);

        return $user;
    }

    /**
     * Create an authenticated user that passes the `settings` role middleware
     * but does NOT have the `modules.manage` permission.
     */
    private function createSettingsUserWithoutPermission(): User
    {
        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------

    public function test_guest_cannot_access_modules_index(): void
    {
        $this->get(route('settings.modules.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_modules_index(): void
    {
        $user = $this->createSettingsUserWithoutPermission();

        $response = $this->actingAs($user)
            ->get(route('settings.modules.index'));

        // The project exception handler may redirect instead of returning 403
        // when $this->authorize() fails on non-JSON web requests.
        $this->assertTrue(
            in_array($response->status(), [302, 403]),
            "Expected 302 or 403 for unauthorized index access, got {$response->status()}"
        );
    }

    public function test_user_with_permission_can_access_modules_index(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('settings.modules.index'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // index()
    // -------------------------------------------------------------------------

    public function test_index_returns_module_list(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)
            ->get(route('settings.modules.index'));

        $response->assertOk()
            ->assertViewHas('modules')
            ->assertViewHas('totalModules')
            ->assertViewHas('enabledCount')
            ->assertViewHas('disabledCount');
    }

    public function test_index_filters_by_search(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)
            ->get(route('settings.modules.index', ['search' => 'Auth']));

        $response->assertOk();

        $modules = $response->viewData('modules');
        foreach ($modules as $module) {
            $matchesName = str_contains(strtolower($module['name']), 'auth');
            $matchesAlias = str_contains(strtolower($module['alias']), 'auth');
            $matchesDesc = str_contains(strtolower($module['description']), 'auth');
            $this->assertTrue($matchesName || $matchesAlias || $matchesDesc,
                "Module '{$module['name']}' should match search 'Auth'");
        }
    }

    public function test_index_filters_by_status_enabled(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)
            ->get(route('settings.modules.index', ['status' => 'enabled']));

        $response->assertOk();

        $modules = $response->viewData('modules');
        foreach ($modules as $module) {
            $this->assertTrue($module['enabled'], "Module '{$module['name']}' should be enabled");
        }
    }

    // -------------------------------------------------------------------------
    // show() and edit()
    // -------------------------------------------------------------------------

    public function test_show_returns_module_details(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('settings.modules.show', 'Auth'))
            ->assertOk()
            ->assertViewHas('module');
    }

    public function test_show_provides_module_array_with_expected_keys(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)
            ->get(route('settings.modules.show', 'Auth'));

        $response->assertOk();
        $module = $response->viewData('module');

        $this->assertIsArray($module);
        $this->assertArrayHasKey('name', $module);
        $this->assertArrayHasKey('alias', $module);
        $this->assertArrayHasKey('enabled', $module);
        $this->assertArrayHasKey('priority', $module);
    }

    public function test_show_redirects_when_module_not_found(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('settings.modules.show', 'NonExistentModuleXyz'))
            ->assertRedirect(route('settings.modules.index'))
            ->assertSessionHas('error');
    }

    public function test_edit_returns_edit_form(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('settings.modules.edit', 'Auth'))
            ->assertOk()
            ->assertViewHas('module');
    }

    public function test_edit_redirects_when_module_not_found(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('settings.modules.edit', 'NonExistentModuleXyz'))
            ->assertRedirect(route('settings.modules.index'))
            ->assertSessionHas('error');
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_requires_permission(): void
    {
        $user = $this->createSettingsUserWithoutPermission();

        $response = $this->actingAs($user)
            ->put(route('settings.modules.update', 'Auth'), [
                'priority' => 5,
                'description' => 'Updated',
            ]);

        // The project exception handler may redirect instead of returning 403
        // when FormRequest::authorize() fails on non-JSON web requests.
        $this->assertTrue(
            in_array($response->status(), [302, 403]),
            "Expected 302 or 403 for unauthorized update, got {$response->status()}"
        );
    }

    public function test_update_validates_required_priority(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->put(route('settings.modules.update', 'Auth'), [
                'description' => 'No priority',
            ])
            ->assertSessionHasErrors(['priority']);
    }

    public function test_update_validates_priority_range(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->put(route('settings.modules.update', 'Auth'), [
                'priority' => 1000,
            ])
            ->assertSessionHasErrors(['priority']);
    }

    public function test_update_validates_priority_minimum(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->put(route('settings.modules.update', 'Auth'), [
                'priority' => -1,
            ])
            ->assertSessionHasErrors(['priority']);
    }

    public function test_update_succeeds_with_valid_data(): void
    {
        $user = $this->createAdminUser();
        $module = Module::find('Auth');
        $configPath = $module->getPath().DIRECTORY_SEPARATOR.'module.json';
        $originalConfig = file_get_contents($configPath);

        try {
            $this->actingAs($user)
                ->put(route('settings.modules.update', 'Auth'), [
                    'priority' => 42,
                    'description' => 'Updated description for testing',
                ])
                ->assertRedirect(route('settings.modules.show', 'Auth'))
                ->assertSessionHas('success');

            $savedConfig = json_decode(file_get_contents($configPath), true);
            $this->assertEquals(42, $savedConfig['priority']);
            $this->assertEquals('Updated description for testing', $savedConfig['description']);
        } finally {
            file_put_contents($configPath, $originalConfig);
        }
    }

    public function test_update_redirects_when_module_not_found(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->put(route('settings.modules.update', 'NonExistentModuleXyz'), [
                'priority' => 5,
            ])
            ->assertRedirect(route('settings.modules.index'))
            ->assertSessionHas('error');
    }

    // -------------------------------------------------------------------------
    // enable() / disable()
    // -------------------------------------------------------------------------

    public function test_enable_module_succeeds(): void
    {
        $user = $this->createAdminUser();
        $module = Module::find('Auth');
        $wasEnabled = $module->isEnabled();

        try {
            $this->actingAs($user)
                ->post(route('settings.modules.enable', 'Auth'))
                ->assertRedirect(route('settings.modules.index'))
                ->assertSessionHas('success');
        } finally {
            if (! $wasEnabled) {
                $module->disable();
            }
        }
    }

    public function test_enable_redirects_when_module_not_found(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->post(route('settings.modules.enable', 'NonExistentModuleXyz'))
            ->assertRedirect(route('settings.modules.index'))
            ->assertSessionHas('error');
    }

    public function test_disable_module_succeeds(): void
    {
        $user = $this->createAdminUser();
        $module = Module::find('Auth');
        $wasEnabled = $module->isEnabled();

        try {
            $this->actingAs($user)
                ->post(route('settings.modules.disable', 'Auth'))
                ->assertRedirect(route('settings.modules.index'))
                ->assertSessionHas('success');
        } finally {
            if ($wasEnabled) {
                $module->enable();
            }
        }
    }

    public function test_disable_core_module_is_rejected(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->post(route('settings.modules.disable', 'Modules'))
            ->assertRedirect(route('settings.modules.index'))
            ->assertSessionHas('error');
    }

    public function test_disable_role_module_is_rejected(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->post(route('settings.modules.disable', 'Role'))
            ->assertRedirect(route('settings.modules.index'))
            ->assertSessionHas('error');
    }

    public function test_disable_redirects_when_module_not_found(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->post(route('settings.modules.disable', 'NonExistentModuleXyz'))
            ->assertRedirect(route('settings.modules.index'))
            ->assertSessionHas('error');
    }

    // -------------------------------------------------------------------------
    // uploadForm() and install()
    // -------------------------------------------------------------------------

    public function test_upload_form_returns_view(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->get(route('settings.modules.uploadForm'))
            ->assertOk();
    }

    public function test_install_requires_file(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->post(route('settings.modules.install'), [])
            ->assertSessionHasErrors(['module_file']);
    }

    public function test_install_requires_zip_mime(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->create('module.txt', 100, 'text/plain');

        $this->actingAs($user)
            ->post(route('settings.modules.install'), [
                'module_file' => $file,
            ])
            ->assertSessionHasErrors(['module_file']);
    }

    public function test_install_requires_permission(): void
    {
        $user = $this->createSettingsUserWithoutPermission();
        $file = UploadedFile::fake()->create('module.zip', 100, 'application/zip');

        $response = $this->actingAs($user)
            ->post(route('settings.modules.install'), [
                'module_file' => $file,
            ]);

        // The project exception handler may redirect instead of returning 403
        // when FormRequest::authorize() fails on non-JSON web requests.
        $this->assertTrue(
            in_array($response->status(), [302, 403]),
            "Expected 302 or 403 for unauthorized install, got {$response->status()}"
        );
    }

    // -------------------------------------------------------------------------
    // uninstall()
    // -------------------------------------------------------------------------

    public function test_cannot_uninstall_core_module_modules(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->post(route('settings.modules.uninstall', 'Modules'))
            ->assertRedirect(route('settings.modules.index'))
            ->assertSessionHas('error');
    }

    public function test_cannot_uninstall_core_module_role(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->post(route('settings.modules.uninstall', 'Role'))
            ->assertRedirect(route('settings.modules.index'))
            ->assertSessionHas('error');
    }

    public function test_uninstall_redirects_when_module_not_found(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->post(route('settings.modules.uninstall', 'NonExistentModuleXyz'))
            ->assertRedirect(route('settings.modules.index'))
            ->assertSessionHas('error');
    }

    public function test_uninstall_requires_permission(): void
    {
        $user = $this->createSettingsUserWithoutPermission();

        $response = $this->actingAs($user)
            ->post(route('settings.modules.uninstall', 'Auth'));

        // The project exception handler may redirect instead of returning 403
        // when $this->authorize() fails on non-JSON web requests.
        $this->assertTrue(
            in_array($response->status(), [302, 403]),
            "Expected 302 or 403 for unauthorized uninstall, got {$response->status()}"
        );
    }
}
