<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Gap de cobertura de alto riesgo (auditoría 14-sep-2026): aplicar una macro
 * ejecuta acciones reales sobre el ticket (cambio de estado/asignación/etc.)
 * y no tenía ningún test que fijara la doble autorización (ticket + macro) ni
 * el resultado de aplicar una macro real.
 *
 * Nota: no existe ninguna restricción "la macro solo aplica a tickets de tal
 * categoría" en el código actual (MacroExecutor::executeAction() y
 * MacroPolicy::apply() no miran ticket->category_id en absoluto) — el
 * hallazgo original de la auditoría suponía que sí, así que ese caso no se
 * cubre aquí; se deja constancia por si algún día se añade esa restricción.
 */
class MacroApplyControllerTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    // No se usa SharesHelpdeskPdo aquí: MacroExecutor::run() abre su PROPIA
    // transacción sobre la conexión 'helpdesk' (DB::connection('helpdesk')->
    // transaction()). Si 'helpdesk' comparte el PDO de 'mariadb' (como hace
    // SharesHelpdeskPdo) esa segunda transacción intenta un beginTransaction()
    // real sobre un PDO que ya está en transacción -> "There is already an
    // active transaction". Con conexiones independientes, Laravel gestiona el
    // anidamiento con SAVEPOINTs sobre la misma conexión sin problema.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    private function agent(): User
    {
        $agent = User::factory()->create();
        $agent->assignRole('helpdesk-agent');
        $agent->givePermissionTo('helpdesk.tickets.update');

        return $agent;
    }

    private function ticket(array $overrides = []): Ticket
    {
        $customer = Customer::firstOrCreate(
            ['email' => 'macro-apply-test@example.com'],
            ['name' => 'Macro Apply Test Customer']
        );

        return Ticket::create(array_merge([
            'subject' => 'Macro apply test ticket',
            'description' => 'Test description.',
            'customer_id' => $customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    public function test_applying_a_macro_executes_its_configured_actions_on_the_ticket(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticket(['priority' => 'low']);

        $macro = Macro::create([
            'name' => 'Escalate to urgent',
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
            ],
            'is_shared' => true,
            'is_active' => true,
            'usage_count' => 0,
        ]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.tickets.macros.apply', [$ticket, $macro]))
            ->assertSuccessful()
            ->assertJson(['success' => true]);

        $this->assertSame('urgent', $ticket->fresh()->priority);
        $this->assertSame(1, $macro->fresh()->usage_count);
    }

    public function test_apply_is_forbidden_when_the_agent_has_no_access_to_the_ticket(): void
    {
        $agent = $this->agent();

        // Un grupo del que el agente NO forma parte: TicketPolicy::inScope()
        // deniega salvo que el ticket no tenga grupo, el agente tenga
        // helpdesk.tickets.manage, o esté en ese grupo — ninguna de las tres
        // aplica aquí.
        $otherGroup = TicketGroup::create([
            'name' => 'Grupo ajeno al agente',
            'assignment_mode' => 'manual',
            'is_active' => true,
        ]);
        $ticket = $this->ticket(['priority' => 'low', 'group_id' => $otherGroup->id]);

        $macro = Macro::create([
            'name' => 'Escalate to urgent',
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
            ],
            'is_shared' => true,
            'is_active' => true,
            'usage_count' => 0,
        ]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.tickets.macros.apply', [$ticket, $macro]))
            ->assertForbidden();

        $this->assertSame('low', $ticket->fresh()->priority, 'la macro no debe haberse aplicado');
    }

    public function test_apply_is_forbidden_when_the_macro_is_private_to_another_agent(): void
    {
        $agent = $this->agent();
        $owner = $this->agent();
        $ticket = $this->ticket(['priority' => 'low']);

        // MacroPolicy::apply() solo permite macros compartidas o propias del
        // usuario autenticado — una macro privada de otro agente debe seguir
        // sin poder aplicarse por id aunque el agente tenga acceso al ticket.
        $macro = Macro::create([
            'name' => 'Private macro of another agent',
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
            ],
            'is_shared' => false,
            'user_id' => $owner->id,
            'is_active' => true,
            'usage_count' => 0,
        ]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.tickets.macros.apply', [$ticket, $macro]))
            ->assertForbidden();

        $this->assertSame('low', $ticket->fresh()->priority);
    }

    public function test_apply_returns_404_when_the_macro_does_not_exist(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticket();

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.tickets.macros.apply', [$ticket, 999999]))
            ->assertNotFound();
    }
}
