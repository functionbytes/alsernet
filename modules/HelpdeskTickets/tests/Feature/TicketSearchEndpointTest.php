<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Buscador de ticket destino para "Fusionar" y "Vincular ticket".
 *
 * Ambos modales exigían teclear el ID numérico a mano, con la ayuda "visible
 * en la URL al abrirlo": había que salir a otra pantalla, copiarlo y volver.
 */
class TicketSearchEndpointTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'search-endpoint-open'],
            ['name' => 'Abierto (search test)']
        );
    }

    private function makeTicket(string $subject): Ticket
    {
        $customer = Customer::create([
            'name' => 'Cliente Buscador',
            'email' => 'buscador-'.uniqid().'@example.invalid',
        ]);

        return Ticket::create([
            'subject' => $subject,
            'customer_id' => $customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'email',
        ]);
    }

    private function buscar(array $params): array
    {
        return $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.search', $params))
            ->assertOk()
            ->json('data');
    }

    public function test_encuentra_por_asunto(): void
    {
        $ticket = $this->makeTicket('Zarandaja fiscal pendiente de revisar');

        $ids = array_column($this->buscar(['q' => 'Zarandaja']), 'id');

        $this->assertContains($ticket->id, $ids);
    }

    public function test_encuentra_por_numero_de_ticket(): void
    {
        $ticket = $this->makeTicket('Otro asunto cualquiera');

        $ids = array_column($this->buscar(['q' => $ticket->ticket_number]), 'id');

        $this->assertContains($ticket->id, $ids);
    }

    public function test_excluye_el_ticket_desde_el_que_se_busca(): void
    {
        // Fusionar un ticket consigo mismo no significa nada.
        $ticket = $this->makeTicket('Zarandaja fiscal pendiente de revisar');

        $ids = array_column($this->buscar(['q' => 'Zarandaja', 'exclude_id' => $ticket->id]), 'id');

        $this->assertNotContains($ticket->id, $ids);
    }

    public function test_una_sola_letra_no_devuelve_medio_listado(): void
    {
        $this->makeTicket('Zarandaja fiscal pendiente de revisar');

        $this->assertSame([], $this->buscar(['q' => 'Z']));
    }

    public function test_devuelve_lo_justo_para_reconocer_el_ticket(): void
    {
        $this->makeTicket('Zarandaja fiscal pendiente de revisar');

        $fila = $this->buscar(['q' => 'Zarandaja'])[0];

        $this->assertArrayHasKey('ticket_number', $fila);
        $this->assertArrayHasKey('subject', $fila);
        $this->assertArrayHasKey('customer_name', $fila);
        $this->assertArrayHasKey('status_name', $fila);
    }

    public function test_exige_estar_autenticado(): void
    {
        $this->getJson(route('manager.helpdesk.tickets.search', ['q' => 'lo que sea']))
            ->assertStatus(401);
    }
}
