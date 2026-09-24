<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Listeners\RecordTicketHistory;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * 24-sep-2026: el listener iba en cola (auth()->id() siempre null: todas las
 * filas sin autor) y duplicaba lo que ya registra TicketObserver (creado,
 * estado, asignación), con el texto "Ticket asignado a " vacío.
 */
class RecordTicketHistoryListenerTest extends TestCase
{
    use SharesHelpdeskPdo;

    public function test_es_sincrono_para_conservar_el_autor(): void
    {
        $this->assertNotInstanceOf(ShouldQueue::class, new RecordTicketHistory);
    }

    public function test_registra_el_cierre_con_el_agente_que_lo_hizo(): void
    {
        $agent = User::factory()->create();
        $this->actingAs($agent);

        $ticket = $this->ticket();

        (new RecordTicketHistory)->handle(new TicketClosed($ticket));

        $row = TicketHistory::where('ticket_id', $ticket->id)->where('action_type', 'ticket_closed')->first();

        $this->assertNotNull($row);
        $this->assertSame($agent->id, (int) $row->user_id);
    }

    public function test_no_duplica_el_alta_que_ya_registra_el_observer(): void
    {
        $ticket = $this->ticket();

        (new RecordTicketHistory)->handle(new TicketCreated($ticket));

        $this->assertSame(0, TicketHistory::where('ticket_id', $ticket->id)->where('action_type', 'ticket_created')->count());
    }

    public function test_ignora_eventos_desconocidos(): void
    {
        (new RecordTicketHistory)->handle(new \stdClass);

        $this->assertTrue(true);
    }

    private function ticket(): Ticket
    {
        $customer = Customer::firstOrCreate(
            ['email' => 'history-listener@example.com'],
            ['name' => 'History Listener'],
        );

        return Ticket::create([
            'subject' => 'History listener '.uniqid(),
            'description' => 'x',
            'customer_id' => $customer->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);
    }
}
