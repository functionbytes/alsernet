<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskContacts\Services\ContactOwnerCatalog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Responsable (owner_id) de Contactos 360: modal Editar, acción masiva
 * "assign", endpoint owners.index, catálogo de agentes asignables y el
 * campo `owner` del Resumen.
 *
 * "Asignable" = usuario con el rol helpdesk-agent y available = 1
 * (ContactOwnerCatalog). Los tests crean sus propios agentes y comparan por
 * inclusión: la base de desarrollo ya tiene agentes reales en ese rol.
 */
class ContactOwnerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = [null, 'mysql', 'helpdesk'];

    private const NOT_ASSIGNABLE = 'El responsable seleccionado no es un agente asignable.';

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);

        // Sin PermissionsSeeder: su backfill reescribe role_has_permissions de
        // los roles reales en cada test y provoca deadlocks entre corridas
        // paralelas. findOrCreate() solo lee cuando el permiso/rol ya existe.
        foreach (['contacts.view', 'contacts.update', 'helpdesk.manage', 'helpdesk.customers.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('helpdesk-agent', 'web');

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function customer(array $attributes = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'email' => 'ct-'.Str::lower(Str::random(12)).'@test.invalid',
            'phone' => null,
            'last_seen_at' => now(),
        ], $attributes));
    }

    /**
     * Agente asignable (rol helpdesk-agent + available) salvo que se indique otra cosa.
     */
    private function agent(array $attributes = [], bool $withRole = true): User
    {
        $user = User::factory()->create(array_merge(['firstname' => 'Ana', 'lastname' => 'Lopez', 'available' => 1], $attributes));

        if ($withRole) {
            $user->assignRole('helpdesk-agent');
        }

        $this->forgetAgentCatalog();

        return $user;
    }

    /**
     * El catálogo de agentes se cachea 60 s (CatalogCacheService::agents()).
     */
    private function forgetAgentCatalog(): void
    {
        Cache::forget('helpdesk:catalogs:agents');
    }

    /**
     * @return array{0: User, 1: Inbox}
     */
    private function scopedAgent(): array
    {
        $inbox = Inbox::create([
            'name' => 'Bandeja '.Str::random(5),
            'channel_type' => Inbox::CHANNEL_WHATSAPP,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['contacts.view', 'contacts.update']);
        AgentInboxCapacity::create(['user_id' => $user->id, 'inbox_id' => $inbox->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        return [$user, $inbox];
    }

    private function inboxCustomer(Inbox $inbox, array $attributes = []): Customer
    {
        $customer = $this->customer($attributes);
        $customer->inboxes()->attach($inbox->id);

        return $customer;
    }

    /**
     * Customer::owner() se resuelve por la conexión 'helpdesk' (Eloquent hereda
     * la del padre) y esa conexión no ve al usuario creado dentro de la
     * transacción del test: la relación se precarga a mano.
     */
    private function customerOwnedBy(User $owner): Customer
    {
        return $this->customer(['owner_id' => $owner->id])->setRelation('owner', $owner);
    }

    private function reloadedOwnedBy(Customer $customer, User $owner): Customer
    {
        return $customer->fresh()->setRelation('owner', $owner);
    }

    private function update(Customer $customer, array $payload, ?User $actor = null): TestResponse
    {
        return $this->actingAs($actor ?? $this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => $customer->name, ...$payload]);
    }

    private function bulk(array $payload, ?User $actor = null): TestResponse
    {
        return $this->actingAs($actor ?? $this->manager)
            ->postJson(route('contacts.bulk-action'), $payload);
    }

    /**
     * @return array<int, int>
     */
    private function listedIds(TestResponse $response): array
    {
        return $response->viewData('customers')->pluck('id')->sort()->values()->all();
    }

    // ── update: asignar ────────────────────────────────────────────────────

    public function test_update_assigns_an_assignable_agent_as_owner(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();

        $this->update($customer, ['owner_id' => $agent->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => $agent->id], 'helpdesk');
    }

    public function test_update_accepts_the_owner_id_as_a_numeric_string(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();

        $this->update($customer, ['owner_id' => (string) $agent->id])->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => $agent->id], 'helpdesk');
    }

    public function test_update_replaces_the_current_owner_with_another_assignable_agent(): void
    {
        $first = $this->agent();
        $second = $this->agent();
        $customer = $this->customer(['owner_id' => $first->id]);

        $this->update($customer, ['owner_id' => $second->id])->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => $second->id], 'helpdesk');
    }

    // ── update: no asignable ───────────────────────────────────────────────

    public function test_update_rejects_a_user_without_the_agent_role(): void
    {
        $notAgent = $this->agent(withRole: false);
        $customer = $this->customer();

        $this->update($customer, ['owner_id' => $notAgent->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['owner_id'])
            ->assertJsonPath('errors.owner_id.0', self::NOT_ASSIGNABLE);

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => null], 'helpdesk');
    }

    public function test_update_rejects_an_unavailable_agent(): void
    {
        $away = $this->agent(['available' => 0]);
        $customer = $this->customer();

        $this->update($customer, ['owner_id' => $away->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.owner_id.0', self::NOT_ASSIGNABLE);

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => null], 'helpdesk');
    }

    public function test_update_rejects_a_user_id_that_does_not_exist(): void
    {
        $customer = $this->customer();

        $this->update($customer, ['owner_id' => 999999999])
            ->assertUnprocessable()
            ->assertJsonPath('errors.owner_id.0', self::NOT_ASSIGNABLE);
    }

    public function test_update_rejects_a_non_integer_owner_id(): void
    {
        $customer = $this->customer();

        $this->update($customer, ['owner_id' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['owner_id']);
    }

    public function test_rejected_owner_does_not_save_the_rest_of_the_form(): void
    {
        $customer = $this->customer(['name' => 'Nombre original']);

        $this->update($customer, ['name' => 'Nombre nuevo', 'owner_id' => 999999999])->assertUnprocessable();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'name' => 'Nombre original'], 'helpdesk');
    }

    // ── update: conservar el actual ────────────────────────────────────────

    public function test_update_keeps_the_current_owner_even_if_no_longer_assignable(): void
    {
        $agent = $this->agent();
        $customer = $this->customer(['owner_id' => $agent->id, 'name' => 'Antes']);
        $agent->update(['available' => 0]);
        $this->forgetAgentCatalog();

        $this->update($customer, ['name' => 'Despues', 'owner_id' => $agent->id])
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => $agent->id, 'name' => 'Despues'], 'helpdesk');
    }

    public function test_update_keeps_the_current_owner_when_it_lost_the_agent_role(): void
    {
        $agent = $this->agent();
        $customer = $this->customer(['owner_id' => $agent->id]);
        $agent->removeRole('helpdesk-agent');
        $this->forgetAgentCatalog();

        $this->update($customer, ['name' => 'Otro', 'owner_id' => $agent->id])->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => $agent->id], 'helpdesk');
    }

    public function test_update_can_move_from_a_no_longer_assignable_owner_to_an_assignable_one(): void
    {
        $old = $this->agent();
        $new = $this->agent();
        $customer = $this->customer(['owner_id' => $old->id]);
        $old->update(['available' => 0]);
        $this->forgetAgentCatalog();

        $this->update($customer, ['owner_id' => $new->id])->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => $new->id], 'helpdesk');
    }

    public function test_update_cannot_move_from_an_old_owner_to_another_non_assignable_user(): void
    {
        $old = $this->agent();
        $notAgent = $this->agent(withRole: false);
        $customer = $this->customer(['owner_id' => $old->id]);

        $this->update($customer, ['owner_id' => $notAgent->id])->assertUnprocessable();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => $old->id], 'helpdesk');
    }

    // ── update: quitar / no tocar ──────────────────────────────────────────

    public function test_update_with_null_owner_removes_the_owner(): void
    {
        $agent = $this->agent();
        $customer = $this->customer(['owner_id' => $agent->id]);

        $this->update($customer, ['owner_id' => null])->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => null], 'helpdesk');
    }

    public function test_update_with_empty_string_owner_removes_the_owner(): void
    {
        $agent = $this->agent();
        $customer = $this->customer(['owner_id' => $agent->id]);

        $this->update($customer, ['owner_id' => ''])->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => null], 'helpdesk');
    }

    public function test_update_without_owner_key_keeps_the_owner(): void
    {
        $agent = $this->agent();
        $customer = $this->customer(['owner_id' => $agent->id]);

        $this->update($customer, ['name' => 'Solo nombre'])->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => $agent->id, 'name' => 'Solo nombre'], 'helpdesk');
    }

    // ── update: autorización ───────────────────────────────────────────────

    public function test_viewer_without_update_permission_cannot_assign_an_owner(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('contacts.view');
        $agent = $this->agent();
        $customer = $this->customer();

        $this->update($customer, ['owner_id' => $agent->id], $viewer)->assertForbidden();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => null], 'helpdesk');
    }

    public function test_restricted_agent_cannot_assign_an_owner_outside_their_inbox(): void
    {
        [$restricted] = $this->scopedAgent();
        $agent = $this->agent();
        $foreign = $this->customer();

        $this->update($foreign, ['owner_id' => $agent->id], $restricted)->assertForbidden();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $foreign->id, 'owner_id' => null], 'helpdesk');
    }

    // ── owners.index y catálogo ────────────────────────────────────────────

    public function test_owners_index_lists_assignable_agents_with_id_and_name(): void
    {
        $agent = $this->agent(['firstname' => 'Beatriz', 'lastname' => 'Campos']);

        $this->actingAs($this->manager)
            ->getJson(route('contacts.owners.index'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['agents' => [['id', 'name']]])
            ->assertJsonFragment(['id' => $agent->id, 'name' => 'Beatriz Campos']);
    }

    public function test_owners_index_excludes_unavailable_users_and_users_without_the_role(): void
    {
        $away = $this->agent(['available' => 0]);
        $notAgent = $this->agent(withRole: false);

        $ids = collect($this->actingAs($this->manager)->getJson(route('contacts.owners.index'))->json('agents'))->pluck('id');

        $this->assertNotContains($away->id, $ids);
        $this->assertNotContains($notAgent->id, $ids);
    }

    public function test_owners_index_is_ordered_by_first_name(): void
    {
        $last = $this->agent(['firstname' => 'Zzzz'.Str::random(4)]);
        $first = $this->agent(['firstname' => 'Aaaa'.Str::random(4)]);

        $ids = collect($this->actingAs($this->manager)->getJson(route('contacts.owners.index'))->json('agents'))->pluck('id');

        $this->assertLessThan($ids->search($last->id), $ids->search($first->id));
    }

    public function test_owners_index_falls_back_to_the_email_when_the_agent_has_no_name(): void
    {
        $agent = $this->agent(['firstname' => '', 'lastname' => '']);

        $this->actingAs($this->manager)
            ->getJson(route('contacts.owners.index'))
            ->assertJsonFragment(['id' => $agent->id, 'name' => $agent->email]);
    }

    public function test_owners_index_does_not_repeat_the_name_when_first_and_last_name_match(): void
    {
        $agent = $this->agent(['firstname' => 'Helena', 'lastname' => 'Helena']);

        $this->actingAs($this->manager)
            ->getJson(route('contacts.owners.index'))
            ->assertJsonFragment(['id' => $agent->id, 'name' => 'Helena']);
    }

    public function test_owners_index_requires_authentication(): void
    {
        $this->getJson(route('contacts.owners.index'))->assertUnauthorized();
    }

    public function test_owners_index_is_forbidden_without_any_contacts_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('contacts.owners.index'))
            ->assertForbidden();
    }

    // Regresión: la ruta owners.index debe exigir contacts.update (la lista de agentes solo sirve al modal Editar).
    public function test_owners_index_is_forbidden_for_a_viewer_without_update_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('contacts.view');

        $this->actingAs($viewer)
            ->getJson(route('contacts.owners.index'))
            ->assertForbidden();
    }

    public function test_catalog_ids_and_is_assignable_reflect_role_and_availability(): void
    {
        $agent = $this->agent();
        $away = $this->agent(['available' => 0]);
        $notAgent = $this->agent(withRole: false);
        $catalog = app(ContactOwnerCatalog::class);

        $this->assertContains($agent->id, $catalog->ids());
        $this->assertTrue($catalog->isAssignable($agent->id));
        $this->assertFalse($catalog->isAssignable($away->id));
        $this->assertFalse($catalog->isAssignable($notAgent->id));
        $this->assertFalse($catalog->isAssignable(999999999));
    }

    public function test_catalog_options_have_integer_ids_and_string_names(): void
    {
        $this->agent();

        foreach (app(ContactOwnerCatalog::class)->options() as $option) {
            $this->assertIsInt($option['id']);
            $this->assertIsString($option['name']);
            $this->assertNotSame('', $option['name']);
        }
    }

    public function test_catalog_agents_returns_user_models(): void
    {
        $agent = $this->agent();

        $agents = app(ContactOwnerCatalog::class)->agents();

        $this->assertInstanceOf(User::class, $agents->firstWhere('id', $agent->id));
    }

    // ── Acción masiva "assign" ─────────────────────────────────────────────

    public function test_bulk_assign_sets_the_owner_on_every_selected_contact(): void
    {
        $agent = $this->agent();
        $a = $this->customer();
        $b = $this->customer();

        $this->bulk(['action' => 'assign', 'ids' => [$a->id, $b->id], 'owner_id' => $agent->id])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 2)
            ->assertJsonPath('message', 'Responsable asignado a 2 contactos');

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $a->id, 'owner_id' => $agent->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customers', ['id' => $b->id, 'owner_id' => $agent->id], 'helpdesk');
    }

    public function test_bulk_assign_overwrites_a_previous_owner(): void
    {
        $old = $this->agent();
        $new = $this->agent();
        $customer = $this->customer(['owner_id' => $old->id]);

        $this->bulk(['action' => 'assign', 'ids' => [$customer->id], 'owner_id' => $new->id])->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => $new->id], 'helpdesk');
    }

    public function test_bulk_assign_with_null_owner_removes_the_owner(): void
    {
        $agent = $this->agent();
        $a = $this->customer(['owner_id' => $agent->id]);
        $b = $this->customer(['owner_id' => $agent->id]);

        $this->bulk(['action' => 'assign', 'ids' => [$a->id, $b->id], 'owner_id' => null])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('message', 'Responsable quitado a 2 contactos');

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $a->id, 'owner_id' => null], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customers', ['id' => $b->id, 'owner_id' => null], 'helpdesk');
    }

    public function test_bulk_assign_requires_the_owner_key_to_be_present(): void
    {
        $customer = $this->customer();

        $this->bulk(['action' => 'assign', 'ids' => [$customer->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['owner_id']);
    }

    public function test_bulk_assign_rejects_a_non_assignable_owner(): void
    {
        $notAgent = $this->agent(withRole: false);
        $customer = $this->customer();

        $this->bulk(['action' => 'assign', 'ids' => [$customer->id], 'owner_id' => $notAgent->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.owner_id.0', self::NOT_ASSIGNABLE);

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => null], 'helpdesk');
    }

    public function test_bulk_assign_rejects_an_owner_that_does_not_exist(): void
    {
        $customer = $this->customer();

        $this->bulk(['action' => 'assign', 'ids' => [$customer->id], 'owner_id' => 999999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['owner_id']);
    }

    public function test_bulk_assign_ignores_contacts_outside_the_agent_scope(): void
    {
        [$restricted, $inbox] = $this->scopedAgent();
        $agent = $this->agent();
        $mine = $this->inboxCustomer($inbox);
        $foreign = $this->customer();

        $this->bulk(['action' => 'assign', 'ids' => [$mine->id, $foreign->id], 'owner_id' => $agent->id], $restricted)
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $mine->id, 'owner_id' => $agent->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customers', ['id' => $foreign->id, 'owner_id' => null], 'helpdesk');
    }

    public function test_bulk_assign_is_forbidden_without_update_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('contacts.view');
        $agent = $this->agent();
        $customer = $this->customer();

        $this->bulk(['action' => 'assign', 'ids' => [$customer->id], 'owner_id' => $agent->id], $viewer)->assertForbidden();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => null], 'helpdesk');
    }

    public function test_owner_id_is_ignored_by_other_bulk_actions(): void
    {
        $customer = $this->customer();

        $this->bulk(['action' => 'ban', 'ids' => [$customer->id], 'owner_id' => 999999999])->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'owner_id' => null], 'helpdesk');
        $this->assertNotNull($customer->fresh()->banned_at);
    }

    // ── Resumen: owner ─────────────────────────────────────────────────────

    public function test_resumen_reports_the_owner_id_and_name(): void
    {
        $agent = $this->agent(['firstname' => 'Carla', 'lastname' => 'Mena']);
        $customer = $this->customerOwnedBy($agent);

        $resumen = app(ContactAggregatorService::class)->resumen($customer);

        $this->assertSame(['id' => $agent->id, 'name' => 'Carla Mena'], $resumen['owner']);
    }

    public function test_resumen_owner_is_null_without_an_owner(): void
    {
        $resumen = app(ContactAggregatorService::class)->resumen($this->customer());

        $this->assertNull($resumen['owner']);
    }

    public function test_resumen_owner_is_null_when_the_owner_user_no_longer_exists(): void
    {
        $customer = $this->customer(['owner_id' => 987654321]);

        $resumen = app(ContactAggregatorService::class)->resumen($customer);

        $this->assertNull($resumen['owner']);
    }

    public function test_resumen_owner_falls_back_to_the_email_when_the_agent_has_no_name(): void
    {
        $agent = $this->agent(['firstname' => '', 'lastname' => '']);
        $customer = $this->customerOwnedBy($agent);

        $resumen = app(ContactAggregatorService::class)->resumen($customer);

        $this->assertSame(['id' => $agent->id, 'name' => $agent->email], $resumen['owner']);
    }

    public function test_resumen_tab_endpoint_exposes_the_owner(): void
    {
        // Customer::owner() se resuelve por la conexión 'helpdesk', que no ve
        // los usuarios creados dentro de la transacción de este test: se usa
        // un usuario real ya confirmado en BD (solo lectura).
        $owner = User::query()->orderBy('id')->firstOrFail();
        $customer = $this->customer(['owner_id' => $owner->id]);

        $this->actingAs($this->manager)
            ->getJson(route('contacts.tab.resumen', $customer))
            ->assertOk()
            ->assertJsonPath('data.owner.id', $owner->id)
            ->assertJsonPath('data.owner.name', trim($owner->full_name) ?: $owner->email);
    }

    public function test_resumen_reflects_an_owner_change_made_through_update(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();
        $service = app(ContactAggregatorService::class);

        $this->assertNull($service->resumen($customer->fresh())['owner']);

        $this->travel(10)->seconds();
        $this->update($customer, ['owner_id' => $agent->id])->assertOk();

        $this->assertSame($agent->id, $service->resumen($this->reloadedOwnedBy($customer, $agent))['owner']['id']);
    }

    public function test_resumen_reflects_an_owner_change_made_through_bulk_assign(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();
        $service = app(ContactAggregatorService::class);

        $this->assertNull($service->resumen($customer->fresh())['owner']);

        $this->travel(10)->seconds();
        $this->bulk(['action' => 'assign', 'ids' => [$customer->id], 'owner_id' => $agent->id])->assertOk();

        $this->assertSame($agent->id, $service->resumen($this->reloadedOwnedBy($customer, $agent))['owner']['id']);
    }

    // ── Filtro ?owner= del listado ─────────────────────────────────────────

    public function test_owner_filter_lists_only_contacts_of_that_owner(): void
    {
        [$restricted, $inbox] = $this->scopedAgent();
        $agent = $this->agent();
        $other = $this->agent();
        $owned = $this->inboxCustomer($inbox, ['owner_id' => $agent->id]);
        $this->inboxCustomer($inbox, ['owner_id' => $other->id]);
        $this->inboxCustomer($inbox);

        $response = $this->actingAs($restricted)
            ->get(route('contacts.index', ['owner' => $agent->id]))
            ->assertOk();

        $this->assertSame([$owned->id], $this->listedIds($response));
    }

    public function test_owner_filter_none_lists_only_unassigned_contacts(): void
    {
        [$restricted, $inbox] = $this->scopedAgent();
        $agent = $this->agent();
        $this->inboxCustomer($inbox, ['owner_id' => $agent->id]);
        $unassigned = $this->inboxCustomer($inbox);

        $response = $this->actingAs($restricted)
            ->get(route('contacts.index', ['owner' => 'none']))
            ->assertOk();

        $this->assertSame([$unassigned->id], $this->listedIds($response));
    }

    public function test_owner_filter_with_a_garbage_value_is_ignored(): void
    {
        [$restricted, $inbox] = $this->scopedAgent();
        $agent = $this->agent();
        $owned = $this->inboxCustomer($inbox, ['owner_id' => $agent->id]);
        $unassigned = $this->inboxCustomer($inbox);

        $response = $this->actingAs($restricted)
            ->get(route('contacts.index', ['owner' => 'abc']))
            ->assertOk();

        $this->assertSame([$owned->id, $unassigned->id], $this->listedIds($response));
    }
}
