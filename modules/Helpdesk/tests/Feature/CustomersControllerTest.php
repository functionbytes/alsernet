<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Customer;
use Tests\TestCase;

class CustomersControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo([
            'helpdesk.customers.view',
            'helpdesk.customers.create',
            'helpdesk.customers.update',
            'helpdesk.customers.delete',
            'helpdesk.customers.manage',
        ]);
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_customers_index(): void
    {
        $this->get(route('manager.helpdesk.customers.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_customers_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('manager.helpdesk.customers.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_customers_index(): void
    {
        Customer::factory()->count(3)->create();

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.customers.index'))
            ->assertOk()
            ->assertViewIs('helpdesk::managers.helpdesk.customers.index')
            ->assertViewHas('customers');
    }

    public function test_manager_can_search_customers(): void
    {
        $customer = Customer::factory()->create(['name' => 'John Searchable']);
        Customer::factory()->create(['name' => 'Jane Other']);

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.customers.index', ['search' => 'Searchable']))
            ->assertOk()
            ->assertSee('John Searchable');
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_manager_can_create_customer(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.customers.store'), [
                'name' => 'New Customer',
                'email' => 'new@example.com',
                'phone' => '+1234567890',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_customers', [
            'name' => 'New Customer',
            'email' => 'new@example.com',
        ]);
    }

    public function test_store_validation_rejects_missing_name_and_email(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.customers.store'), [])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_store_validation_rejects_duplicate_email(): void
    {
        Customer::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.customers.store'), [
                'name' => 'Another Customer',
                'email' => 'existing@example.com',
            ])
            ->assertSessionHasErrors(['email']);
    }

    public function test_user_without_create_permission_cannot_store_customer(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.customers.view');

        $this->actingAs($user)
            ->post(route('manager.helpdesk.customers.store'), [
                'name' => 'Test',
                'email' => 'test@example.com',
            ])
            ->assertForbidden();
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_manager_can_update_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.customers.update', $customer), [
                'name' => 'Updated Name',
                'email' => $customer->email,
            ])
            ->assertRedirect(route('manager.helpdesk.customers.show', $customer));

        $this->assertDatabaseHas('helpdesk_customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_validation_allows_same_email_for_same_customer(): void
    {
        $customer = Customer::factory()->create(['email' => 'original@example.com']);

        $this->actingAs($this->manager)
            ->put(route('manager.helpdesk.customers.update', $customer), [
                'name' => 'Updated Name',
                'email' => 'original@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_customers', [
            'id' => $customer->id,
            'email' => 'original@example.com',
        ]);
    }

    public function test_user_without_update_permission_cannot_update_customer(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.customers.view');
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->put(route('manager.helpdesk.customers.update', $customer), [
                'name' => 'Updated',
                'email' => $customer->email,
            ])
            ->assertForbidden();
    }

    // ─── destroy / restore ────────────────────────────────────────────────────

    public function test_manager_can_soft_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->manager)
            ->delete(route('manager.helpdesk.customers.destroy', $customer))
            ->assertRedirect(route('manager.helpdesk.customers.index'));

        $this->assertSoftDeleted('helpdesk_customers', ['id' => $customer->id]);
    }

    public function test_user_without_delete_permission_cannot_destroy_customer(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.customers.view');
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->delete(route('manager.helpdesk.customers.destroy', $customer))
            ->assertForbidden();
    }

    public function test_manager_can_restore_soft_deleted_customer(): void
    {
        $customer = Customer::factory()->create();
        $customer->delete();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.customers.restore', $customer->id))
            ->assertRedirect(route('manager.helpdesk.customers.show', $customer));

        $this->assertDatabaseHas('helpdesk_customers', [
            'id' => $customer->id,
            'deleted_at' => null,
        ]);
    }

    // ─── ban / unban ──────────────────────────────────────────────────────────

    public function test_manager_can_ban_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.customers.ban', $customer))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_customers', [
            'id' => $customer->id,
            'is_banned' => true,
        ]);
    }

    public function test_manager_can_unban_customer(): void
    {
        $customer = Customer::factory()->banned()->create();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.customers.unban', $customer))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_customers', [
            'id' => $customer->id,
            'is_banned' => false,
        ]);
    }
}
