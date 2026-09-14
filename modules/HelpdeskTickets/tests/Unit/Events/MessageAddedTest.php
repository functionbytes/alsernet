<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Models\TicketItem;
use Tests\TestCase;

/**
 * Antes un correo entrante real con el ticket ya abierto no aparecía hasta
 * recargar la página a mano: MessageAdded no transmitía nada. Estos tests
 * cubren solo la forma del evento (canal/nombre/payload) — no necesitan BD,
 * el item nunca se persiste.
 */
class MessageAddedTest extends TestCase
{
    private function makeItem(int $id = 42, int $ticketId = 7, bool $internal = false): TicketItem
    {
        return (new TicketItem)->forceFill([
            'id' => $id,
            'ticket_id' => $ticketId,
            'is_internal' => $internal,
        ]);
    }

    public function test_implementa_should_broadcast(): void
    {
        $event = new MessageAdded($this->makeItem());

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
    }

    public function test_transmite_en_el_canal_de_presencia_del_ticket(): void
    {
        // Mismo canal que TicketViewing/TicketTyping (routes/channels.php),
        // no uno nuevo — así el frontend solo necesita un listener más en la
        // suscripción que ya tiene.
        $event = new MessageAdded($this->makeItem(ticketId: 99));

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PresenceChannel::class, $channels[0]);
        $this->assertSame('presence-ticket.99', $channels[0]->name);
    }

    public function test_broadcast_as_es_message_added(): void
    {
        $event = new MessageAdded($this->makeItem());

        $this->assertSame('message.added', $event->broadcastAs());
    }

    public function test_el_payload_es_minimo_a_proposito(): void
    {
        // El frontend vuelve a pedir TicketDetailDataController::data()
        // completo al recibirlo (fetchDetailData()) en vez de reconstruir el
        // hilo aquí — el payload no debe crecer para llevar más que lo
        // necesario para decidir si hace falta refrescar.
        $event = new MessageAdded($this->makeItem(id: 123, internal: true));

        $this->assertSame(['item_id' => 123, 'is_internal' => true], $event->broadcastWith());
    }
}
