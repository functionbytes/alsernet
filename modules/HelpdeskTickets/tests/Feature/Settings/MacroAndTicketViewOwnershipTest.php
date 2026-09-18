<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\TicketView;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Auditoría de seguridad (14-sep-2026): MacrosController y
 * TicketViewsController (settings) solo exigían el permiso de ruta
 * helpdesk.tickets.settings — que en producción tienen 77+ cuentas
 * distintas (roles super-settings/helpdesk-admin/super-admin), no una sola
 * cuenta de administrador — pero no comprobaban is_shared/user_id del
 * macro/vista antes de editarlo o borrarlo. Cualquier cuenta con ese
 * permiso podía mutar por id el macro o la vista PRIVADA de OTRA cuenta con
 * el mismo permiso. TicketCannedRepliesController ya hacía este chequeo
 * (canBeEditedBy()); estos dos controladores no.
 */
class MacroAndTicketViewOwnershipTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);
    }

    private function settingsUser(): User
    {
        $user = User::factory()->create();
        // Las rutas manager.helpdesk.* van además envueltas en
        // role:helpdesk-agent|helpdesk-manager|manager|super-admin|super-settings
        // (HelpdeskTicketsServiceProvider) — sin un rol de esa lista, CUALQUIER
        // request a estas rutas devuelve 403 en el middleware de rol, ANTES de
        // llegar al controlador. Sin esto, los 2 primeros intentos de este test
        // (con solo el permiso, sin rol) daban 403 tanto al intruso como al
        // propio dueño por el motivo EQUIVOCADO — el caso "intruso" parecía en
        // verde pero no estaba ejercitando authorizeOwnership() en absoluto.
        //
        // 'helpdesk-agent', NO 'super-settings'/'super-admin'/'helpdesk-manager':
        // confirmado por tinker que esos tres YA traen helpdesk.tickets.manage
        // de fábrica (rol → permiso seedeado), lo que haría que canManage()
        // pase por la rama .manage para AMBOS usuarios y el test de "el
        // intruso no puede" fuera un falso positivo — 'helpdesk-agent' (como
        // 'manager') no trae ni .manage ni .settings por defecto, así que el
        // único permiso en juego es el que se da explícito abajo, igual que
        // las 77+ cuentas reales del hallazgo (settings sin manage).
        $user->assignRole('helpdesk-agent');
        // Permiso directo (no via rol): en producción lo tienen 77+ cuentas
        // repartidas entre super-settings/helpdesk-admin/super-admin, aquí
        // basta con dárselo directo para no depender de qué rol lo trae en
        // cada seeder.
        $user->givePermissionTo('helpdesk.tickets.settings');

        return $user;
    }

    private function privateMacro(int $ownerId): Macro
    {
        return Macro::create([
            'name' => 'Macro privada '.uniqid(),
            'description' => 'test',
            'actions' => [['type' => array_key_first(Macro::$actionTypes)]],
            'is_shared' => false,
            'user_id' => $ownerId,
            'is_active' => true,
        ]);
    }

    private function privateView(int $ownerId): TicketView
    {
        return TicketView::create([
            'user_id' => $ownerId,
            'ticket_id' => null,
            'name' => 'Vista privada '.uniqid(),
            'filters' => [],
            'is_default' => false,
            'is_shared' => false,
            'is_system' => false,
        ]);
    }

    public function test_agent_cannot_edit_another_agents_private_macro(): void
    {
        $owner = $this->settingsUser();
        $intruder = $this->settingsUser();

        $macro = $this->privateMacro($owner->id);

        $this->actingAs($intruder)
            ->get(route('manager.helpdesk.settings.macros.edit', $macro))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->put(route('manager.helpdesk.settings.macros.update', $macro), [
                'name' => 'Hackeado',
                // 'reply' (array_key_first) exige 'body' en UpdateMacroRequest
                // — sin él, la request fallaba en VALIDACIÓN (302) antes de
                // llegar siquiera a authorizeOwnership(), dando un falso
                // negativo (302 != 403 esperado, pero por el motivo
                // equivocado: nunca se llegó a comprobar la propiedad).
                'actions' => json_encode([['type' => array_key_first(Macro::$actionTypes), 'body' => 'x']]),
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('manager.helpdesk.settings.macros.destroy', $macro))
            ->assertForbidden();

        $this->assertDatabaseHas('helpdesk_macros', [
            'id' => $macro->id,
            'name' => $macro->name,
        ], 'helpdesk');
    }

    public function test_owner_can_still_edit_their_own_private_macro(): void
    {
        $owner = $this->settingsUser();
        $this->assertTrue($owner->can('helpdesk.tickets.settings'), 'owner should have settings permission');
        $macro = $this->privateMacro($owner->id);

        $this->actingAs($owner)
            ->get(route('manager.helpdesk.settings.macros.edit', $macro))
            ->assertOk();
    }

    public function test_agent_cannot_edit_another_agents_private_ticket_view(): void
    {
        $owner = $this->settingsUser();
        $intruder = $this->settingsUser();

        $view = $this->privateView($owner->id);

        $this->actingAs($intruder)
            ->get(route('manager.helpdesk.settings.ticket-views.edit', $view))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->put(route('manager.helpdesk.settings.ticket-views.update', $view), [
                'name' => 'Hackeada',
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('manager.helpdesk.settings.ticket-views.destroy', $view))
            ->assertForbidden();

        $this->assertDatabaseHas('helpdesk_ticket_views', [
            'id' => $view->id,
            'name' => $view->name,
        ], 'helpdesk');
    }

    public function test_owner_can_still_edit_their_own_private_ticket_view(): void
    {
        $owner = $this->settingsUser();
        $view = $this->privateView($owner->id);

        $this->actingAs($owner)
            ->get(route('manager.helpdesk.settings.ticket-views.edit', $view))
            ->assertOk();
    }
}
