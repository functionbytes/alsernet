<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Hilo del panel de detalle (/panel/helpdesk/tickets/{ticket}/data).
 *
 * Dos fallos reales del QA del 28-sep-2026:
 *  - la hora de cada mensaje salía en UTC (la zona de la app) mientras el
 *    listado la pintaba en la hora local del agente: 11:52 frente a 13:52;
 *  - un ticket sin ningún mensaje (los recurrentes, o uno web con solo
 *    `description`) mostraba "no tiene mensajes" aunque tuviera contenido.
 */
class TicketDetailThreadDisplayTest extends TestCase
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
            ['slug' => 'thread-display-test-open'],
            ['name' => 'Abierto (thread display test)']
        );
    }

    private function makeTicket(array $attributes = []): Ticket
    {
        $customer = Customer::create([
            'name' => 'Cliente Hilo',
            'email' => 'thread-'.uniqid().'@example.invalid',
        ]);

        return Ticket::create(array_merge([
            'subject' => 'Ticket del hilo',
            'customer_id' => $customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $attributes));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function thread(Ticket $ticket, array $query = []): array
    {
        return $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', $ticket).'?'.http_build_query($query))
            ->assertOk()
            ->json('thread');
    }

    public function test_la_hora_del_mensaje_sale_en_la_zona_de_visualizacion_y_no_en_utc(): void
    {
        $ticket = $this->makeTicket();
        $item = TicketItem::create([
            'ticket_id' => $ticket->id,
            'type' => 'message',
            'body' => 'Hola',
            'is_internal' => false,
        ]);
        // 11:52 UTC en horario de verano = 13:52 en Madrid.
        $item->forceFill(['created_at' => Carbon::parse('2026-09-28 11:52:00', 'UTC')])->save();

        $message = collect($this->thread($ticket))->firstWhere('id', $item->id);

        $this->assertSame('13:52', $message['time']);
        // El ISO sigue viajando en UTC: el navegador lo convierte por su lado.
        $this->assertSame('2026-09-28T11:52:00+00:00', $message['created_at']);
    }

    public function test_un_ticket_sin_mensajes_muestra_su_descripcion_como_mensaje_inicial(): void
    {
        $ticket = $this->makeTicket(['description' => 'No me llega el pedido']);

        $thread = $this->thread($ticket);

        $this->assertCount(1, collect($thread)->where('type', 'message'));
        $first = collect($thread)->firstWhere('type', 'message');
        $this->assertSame('No me llega el pedido', $first['body']);
        $this->assertFalse($first['from_agent']);
    }

    public function test_la_descripcion_no_se_duplica_si_ya_hay_mensajes_reales(): void
    {
        $ticket = $this->makeTicket(['description' => 'Texto original']);
        TicketItem::create([
            'ticket_id' => $ticket->id,
            'type' => 'message',
            'body' => 'Texto original',
            'is_internal' => false,
        ]);

        $messages = collect($this->thread($ticket))->where('type', 'message');

        $this->assertCount(1, $messages);
        $this->assertGreaterThan(0, $messages->first()['id']);
    }

    public function test_la_descripcion_respeta_la_busqueda_en_el_hilo(): void
    {
        $ticket = $this->makeTicket(['description' => 'No me llega el pedido']);

        $this->assertCount(0, collect($this->thread($ticket, ['thread_search' => 'factura']))->where('type', 'message'));
        $this->assertCount(1, collect($this->thread($ticket, ['thread_search' => 'pedido']))->where('type', 'message'));
    }
}
