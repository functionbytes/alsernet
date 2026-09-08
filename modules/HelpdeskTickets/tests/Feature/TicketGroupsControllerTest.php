<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketGroupsController;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketGroupsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketGroupsController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketGroup::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-groups.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-groups.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-groups.destroy'));
    }

    /**
     * Regresion (ago-2026): el selector de miembros filtraba por
     * available + verified. Ningun agente real tiene verified=1 (esa columna
     * es la verificacion de email de Auth, que solo pasan las altas por
     * registro publico), asi que la lista solo mostraba usuarios de factory
     * fugados de los tests y ni un solo agente del equipo.
     */
    public function test_member_picker_lists_agents_without_email_verification(): void
    {
        [$manager, $agent] = $this->makeManagerAndAgent();

        $response = $this->actingAs($manager)
            ->get(route('manager.helpdesk.settings.ticket-groups.create'));

        $response->assertOk();
        $response->assertSee($agent->firstname.' '.$agent->lastname);
        $this->assertSame(0, (int) $agent->verified, 'el agente de la prueba debe tener verified=0');
    }

    public function test_member_picker_excludes_users_without_the_agent_role(): void
    {
        [$manager] = $this->makeManagerAndAgent();

        $outsider = User::factory()->create([
            'firstname' => 'Zzz',
            'lastname' => 'NoEsAgente',
            'available' => 1,
            'verified' => 1,
        ]);

        CatalogCacheService::invalidate();

        $response = $this->actingAs($manager)
            ->get(route('manager.helpdesk.settings.ticket-groups.create'));

        $response->assertOk();
        $response->assertDontSee($outsider->firstname.' '.$outsider->lastname);
    }

    public function test_edit_uses_the_same_agent_source_as_create(): void
    {
        [$manager, $agent] = $this->makeManagerAndAgent();

        $group = TicketGroup::create([
            'name' => 'Grupo de prueba '.uniqid(),
            'assignment_mode' => 'manual',
            'is_active' => true,
            'is_default' => false,
        ]);

        $response = $this->actingAs($manager)
            ->get(route('manager.helpdesk.settings.ticket-groups.edit', $group));

        $response->assertOk();
        $response->assertSee($agent->firstname.' '.$agent->lastname);
    }

    /**
     * total_members contaba sobre helpdesk_ticket_group_user, la tabla
     * histórica que quedó huérfana con la unificación de helpdesk_groups
     * (TicketGroup::users() usa helpdesk_group_user desde entonces) — daba
     * 0 siempre, sin importar cuántos agentes tuviera un grupo real.
     */
    public function test_total_members_cuenta_sobre_la_tabla_real_del_pivot(): void
    {
        [$manager, $agent] = $this->makeManagerAndAgent();

        $group = TicketGroup::create(['name' => 'Grupo con miembros'.uniqid()]);
        $group->users()->attach($agent->id, ['conversation_priority' => 'primary']);

        $response = $this->actingAs($manager)
            ->get(route('manager.helpdesk.settings.ticket-groups.index'));

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats['total_members'] >= 1);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function makeManagerAndAgent(): array
    {
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);
        $settingsRole = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $settingsRole->givePermissionTo('helpdesk.tickets.settings');
        $agentRole = Role::firstOrCreate(['name' => 'helpdesk-agent', 'guard_name' => 'web']);

        $manager = User::factory()->create();
        $manager->assignRole($settingsRole);

        // Como los agentes reales: alta directa, sin verificacion de email.
        $agent = User::factory()->create([
            'firstname' => 'Agente',
            'lastname' => 'DePrueba'.uniqid(),
            'available' => 1,
            'verified' => 0,
        ]);
        $agent->assignRole($agentRole);

        CatalogCacheService::invalidate();

        return [$manager, $agent];
    }
}
