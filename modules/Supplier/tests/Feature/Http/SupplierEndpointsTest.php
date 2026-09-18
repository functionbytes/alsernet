<?php

namespace Modules\Supplier\Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Supplier\Database\Factories\Supplier\SupplierFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplierEndpointsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')
            || ! Schema::hasTable('permissions')) {
            $this->markTestSkipped('Required tables (users, permissions) are not present in the test database. Migrate the test DB to run these tests.');
        }

        activity()->disableLogging();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function userWithPermission(string $permission): User
    {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user = User::factory()->create(['available' => true]);
        $user->givePermissionTo($permission);

        return $user;
    }

    private function userWithoutPermission(): User
    {
        return User::factory()->create(['available' => true]);
    }

    // =========================================================================
    // Suppliers index (GET /panel/suppliers)
    // =========================================================================

    public function test_guest_cannot_access_suppliers_index(): void
    {
        $this->get(route('suppliers.index'))->assertRedirect();
    }

    public function test_user_without_permission_gets_403_on_suppliers_index(): void
    {
        $user = $this->userWithoutPermission();

        $this->actingAs($user)
            ->get(route('suppliers.index'))
            ->assertForbidden();
    }

    public function test_user_with_permission_can_access_suppliers_index(): void
    {
        $user = $this->userWithPermission('suppliers.view');

        $this->actingAs($user)
            ->get(route('suppliers.index'))
            ->assertOk();
    }

    // =========================================================================
    // Suppliers show (GET /panel/suppliers/show/{uid})
    // =========================================================================

    public function test_user_with_permission_can_access_supplier_show(): void
    {
        $user = $this->userWithPermission('suppliers.view');
        $supplier = SupplierFactory::new()->create();

        $this->actingAs($user)
            ->get(route('suppliers.show', $supplier->uid))
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_user_without_permission_gets_403_on_supplier_show(): void
    {
        $user = $this->userWithoutPermission();
        $supplier = SupplierFactory::new()->create();

        $this->actingAs($user)
            ->get(route('suppliers.show', $supplier->uid))
            ->assertForbidden();
    }

    public function test_supplier_show_returns_404_for_nonexistent_uid(): void
    {
        $user = $this->userWithPermission('suppliers.view');

        $this->actingAs($user)
            ->get(route('suppliers.show', 'nonexistent-uid-xyz'))
            ->assertNotFound();
    }

    // =========================================================================
    // Supplier toggle (POST /panel/suppliers/{uid}/toggle)
    // =========================================================================

    public function test_user_with_permission_can_toggle_supplier(): void
    {
        $user = $this->userWithPermission('suppliers.configure');
        $supplier = SupplierFactory::new()->create(['available' => true]);

        $this->actingAs($user)
            ->postJson(route('suppliers.toggle', $supplier->uid))
            ->assertOk()
            ->assertJsonStructure(['success', 'available']);

        $supplier->refresh();
        $this->assertFalse($supplier->available);
    }

    public function test_user_without_permission_gets_403_on_toggle(): void
    {
        $user = $this->userWithoutPermission();
        $supplier = SupplierFactory::new()->create();

        $this->actingAs($user)
            ->postJson(route('suppliers.toggle', $supplier->uid))
            ->assertForbidden();
    }

    // =========================================================================
    // Supplier update (PUT /panel/suppliers/{uid})
    // =========================================================================

    public function test_user_with_permission_can_update_supplier(): void
    {
        $user = $this->userWithPermission('suppliers.configure');
        $supplier = SupplierFactory::new()->create();

        $this->actingAs($user)
            ->putJson(route('suppliers.update', $supplier->uid), [
                'label' => 'Updated Label',
                'code' => $supplier->code,
                'available' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $supplier->refresh();
        $this->assertSame('Updated Label', $supplier->label);
    }

    public function test_user_without_permission_gets_403_on_update(): void
    {
        $user = $this->userWithoutPermission();
        $supplier = SupplierFactory::new()->create();

        $this->actingAs($user)
            ->putJson(route('suppliers.update', $supplier->uid), [
                'label' => 'Hack attempt',
                'code' => $supplier->code,
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // Settings suppliers index (GET /panel/setting/suppliers/suppliers)
    // =========================================================================

    public function test_user_with_permission_can_access_settings_suppliers_index(): void
    {
        $user = $this->userWithPermission('suppliers.view');

        $this->actingAs($user)
            ->get(route('settings.suppliers.index'))
            ->assertOk();
    }

    public function test_user_without_permission_gets_403_on_settings_suppliers_index(): void
    {
        $user = $this->userWithoutPermission();

        $this->actingAs($user)
            ->get(route('settings.suppliers.index'))
            ->assertForbidden();
    }

    // =========================================================================
    // Sync index (GET /panel/setting/suppliers/sync)
    // =========================================================================

    public function test_user_with_sync_permission_can_access_sync_index(): void
    {
        Permission::firstOrCreate(['name' => 'can_sync_suppliers', 'guard_name' => 'web']);
        $user = $this->userWithPermission('suppliers.sync.status');
        $user->givePermissionTo('can_sync_suppliers');

        $this->actingAs($user)
            ->get(route('settings.suppliers.sync.index'))
            ->assertOk();
    }

    public function test_user_without_sync_permission_gets_403_on_sync_index(): void
    {
        $user = $this->userWithoutPermission();

        $this->actingAs($user)
            ->get(route('settings.suppliers.sync.index'))
            ->assertForbidden();
    }

    // =========================================================================
    // Content index (GET /panel/setting/suppliers/content)
    // =========================================================================

    public function test_user_with_content_permission_can_access_content_index(): void
    {
        $user = $this->userWithPermission('suppliers.view.content');

        $this->actingAs($user)
            ->get(route('settings.suppliers.content.index'))
            ->assertOk();
    }

    public function test_user_without_content_permission_gets_403_on_content_index(): void
    {
        $user = $this->userWithoutPermission();

        $this->actingAs($user)
            ->get(route('settings.suppliers.content.index'))
            ->assertForbidden();
    }

    // =========================================================================
    // Sources index (GET /panel/suppliers/{supplierUid}/sources)
    // =========================================================================

    public function test_user_with_sources_permission_can_access_sources_index(): void
    {
        $user = $this->userWithPermission('suppliers.sources.manage');
        $supplier = SupplierFactory::new()->create();

        $this->actingAs($user)
            ->get(route('suppliers.sources.index', $supplier->uid))
            ->assertOk();
    }

    public function test_user_without_sources_permission_gets_403_on_sources_index(): void
    {
        $user = $this->userWithoutPermission();
        $supplier = SupplierFactory::new()->create();

        $this->actingAs($user)
            ->get(route('suppliers.sources.index', $supplier->uid))
            ->assertForbidden();
    }

    // =========================================================================
    // Bulk action on suppliers
    // =========================================================================

    public function test_bulk_enable_action_updates_suppliers(): void
    {
        $user = $this->userWithPermission('suppliers.configure');
        $s1 = SupplierFactory::new()->inactive()->create();
        $s2 = SupplierFactory::new()->inactive()->create();

        $this->actingAs($user)
            ->postJson(route('settings.suppliers.bulk-action'), [
                'action' => 'enable',
                'uids' => [$s1->uid, $s2->uid],
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'count']);

        $this->assertDatabaseHas('suppliers', ['uid' => $s1->uid, 'available' => true]);
        $this->assertDatabaseHas('suppliers', ['uid' => $s2->uid, 'available' => true]);
    }

    public function test_bulk_delete_action_removes_suppliers(): void
    {
        $user = $this->userWithPermission('suppliers.configure');
        $supplier = SupplierFactory::new()->create();

        $this->actingAs($user)
            ->postJson(route('settings.suppliers.bulk-action'), [
                'action' => 'delete',
                'uids' => [$supplier->uid],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('suppliers', ['uid' => $supplier->uid]);
    }

    public function test_bulk_action_with_invalid_action_returns_422(): void
    {
        $user = $this->userWithPermission('suppliers.configure');

        $this->actingAs($user)
            ->postJson(route('settings.suppliers.bulk-action'), [
                'action' => 'invalid_action',
                'uids' => ['some-uid'],
            ])
            ->assertUnprocessable();
    }
}
