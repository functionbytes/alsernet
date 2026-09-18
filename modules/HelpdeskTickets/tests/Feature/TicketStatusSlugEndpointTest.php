<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Endpoint que alimenta el boton "generar slug" de los formularios de estado.
 */
class TicketStatusSlugEndpointTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' es la conexion por defecto (usuarios, roles y permisos) y
    // 'helpdesk' la de los estados: sin las dos, lo que crea el test se queda
    // escrito en la base real.
    protected array $connectionsToTransact = ['mysql', 'helpdesk'];

    private User $manager;

    private User $unauthorized;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $role->givePermissionTo('helpdesk.tickets.settings');

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        $this->unauthorized = User::factory()->create();
    }

    private function slugFor(string $name, ?int $ignoreId = null)
    {
        return $this->actingAs($this->manager)->postJson(
            route('manager.helpdesk.settings.ticket-statuses.ajax-slug'),
            array_filter(['name' => $name, 'ignoreId' => $ignoreId])
        );
    }

    public function test_it_slugifies_the_name(): void
    {
        $this->slugFor('Accion Critica Urgente')
            ->assertOk()
            ->assertJson(['slug' => 'accion-critica-urgente']);
    }

    public function test_it_strips_accents_like_str_slug(): void
    {
        $this->slugFor('Técnico Avanzado')
            ->assertOk()
            ->assertJson(['slug' => 'tecnico-avanzado']);
    }

    public function test_it_suffixes_a_slug_already_taken(): void
    {
        TicketStatus::create([
            'name' => 'Pendiente de revision',
            'slug' => 'pendiente-de-revision',
            'color' => '#90bb13',
        ]);

        $this->slugFor('Pendiente de revision')
            ->assertOk()
            ->assertJson(['slug' => 'pendiente-de-revision-2']);
    }

    public function test_it_keeps_climbing_while_the_suffix_is_taken(): void
    {
        foreach (['en-revision', 'en-revision-2'] as $slug) {
            TicketStatus::create(['name' => $slug, 'slug' => $slug, 'color' => '#90bb13']);
        }

        $this->slugFor('En revision')
            ->assertOk()
            ->assertJson(['slug' => 'en-revision-3']);
    }

    public function test_a_status_does_not_collide_with_itself(): void
    {
        $status = TicketStatus::create([
            'name' => 'Escalado a nivel 2',
            'slug' => 'escalado-a-nivel-2',
            'color' => '#90bb13',
        ]);

        $this->slugFor('Escalado a nivel 2', $status->id)
            ->assertOk()
            ->assertJson(['slug' => 'escalado-a-nivel-2']);
    }

    public function test_a_name_without_slugifiable_characters_returns_empty(): void
    {
        $this->slugFor('!!! ???')
            ->assertOk()
            ->assertJson(['slug' => '']);
    }

    public function test_it_requires_a_name(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.settings.ticket-statuses.ajax-slug'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_it_rejects_users_without_the_settings_permission(): void
    {
        $this->actingAs($this->unauthorized)
            ->postJson(route('manager.helpdesk.settings.ticket-statuses.ajax-slug'), ['name' => 'Nuevo'])
            ->assertForbidden();
    }

    public function test_it_rejects_guests(): void
    {
        $this->postJson(route('manager.helpdesk.settings.ticket-statuses.ajax-slug'), ['name' => 'Nuevo'])
            ->assertUnauthorized();
    }
}
