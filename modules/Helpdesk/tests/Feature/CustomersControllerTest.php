<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

class CustomersControllerTest extends HelpdeskTestCase
{
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
            ->assertViewIs('helpdesk::helpdesk.customers.index')
            ->assertViewHas('customers');
    }

    public function test_manager_can_search_customers(): void
    {
        // forAgent() es fail-closed: sin el permiso plano helpdesk.manage (o un
        // inbox asignado) el listado sale vacío — mismo patrón que el test AJAX.
        $this->manager->givePermissionTo('helpdesk.manage');

        $customer = Customer::factory()->create(['name' => 'John Searchable']);
        Customer::factory()->create(['name' => 'Jane Other']);

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.customers.index', ['search' => 'Searchable']))
            ->assertOk()
            ->assertSee('John Searchable');
    }

    // ─── search (AJAX, usado por los modales de nueva conversación/vincular) ───

    public function test_ajax_search_falls_back_to_whatsapp_phone_when_phone_is_missing(): void
    {
        // forAgent() gates on the raw Spatie permission (not the role name), y
        // PermissionsSeeder solo crea los permisos sin asignarlos a ningún rol
        // — hay que otorgarlo explícitamente para ejercitar la rama "manager".
        $this->manager->givePermissionTo('helpdesk.manage');

        $customer = Customer::factory()->create([
            'name' => 'Solo WhatsApp '.uniqid(),
            'email' => null,
            'phone' => null,
            'whatsapp_phone' => '34600111222',
        ]);

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.customers.search', ['q' => $customer->whatsapp_phone]))
            ->assertOk()
            ->assertJsonFragment(['id' => $customer->id, 'phone' => '34600111222']);
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
        ], 'helpdesk');
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
        ], 'helpdesk');
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
        ], 'helpdesk');
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

        $this->assertSoftDeleted('helpdesk_customers', ['id' => $customer->id], 'helpdesk');
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
        ], 'helpdesk');
    }

    // ─── ban / unban ──────────────────────────────────────────────────────────

    public function test_manager_can_ban_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.customers.ban', $customer))
            ->assertRedirect();

        $this->assertNotNull(Customer::find($customer->id)->banned_at);
    }

    public function test_manager_can_unban_customer(): void
    {
        $customer = Customer::factory()->banned()->create();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.customers.unban', $customer))
            ->assertRedirect();

        $this->assertNull(Customer::find($customer->id)->banned_at);
    }

    // ─── visibilidad por inbox (CustomerPolicy scoping) ─────────────────────────

    public function test_agent_without_shared_inbox_cannot_view_customer(): void
    {
        $agent = User::factory()->create();
        $agent->givePermissionTo('helpdesk.customers.view');
        $customer = Customer::factory()->create();

        // Tiene el permiso global pero no comparte inbox con el cliente:
        // CustomerPolicy::view aplica el aislamiento por inbox para agentes.
        $this->actingAs($agent)
            ->get(route('manager.helpdesk.customers.show', $customer))
            ->assertForbidden();
    }
}
