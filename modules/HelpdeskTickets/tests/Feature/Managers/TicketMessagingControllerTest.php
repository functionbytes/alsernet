<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\NewTicketMessage;
use Modules\HelpdeskTickets\Events\TicketMessageReceived;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Cubre el fix de seguridad del 14-sep-2026 (auditoría): createMessageItem()
 * disparaba broadcast(new TicketMessageReceived($ticket, $item)) SIEMPRE,
 * incluso para una nota interna ($isInternal true) — y TicketMessageReceived
 * transmite en new Channel('ticket.'.$id), un canal PÚBLICO sin autenticar
 * (a propósito, para el widget de cliente), con el body/html_body completos
 * en broadcastWith(). Cualquiera suscrito a ese canal por websocket, sin
 * login, recibía el contenido de la nota interna.
 *
 * Ninguno de los dos eventos (TicketMessageReceived, NewTicketMessage) tenía
 * además listener ni consumidor real en el JS de este módulo — se retiraron
 * de createMessageItem() por completo en vez de solo guardarlos tras un
 * `if (! $isInternal)`.
 */
class TicketMessagingControllerTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private User $manager;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('super-settings');
        $this->manager->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $customer = Customer::firstOrCreate(
            ['email' => 'messaging-test-customer@example.com'],
            ['name' => 'Messaging Test Customer']
        );

        $this->ticket = Ticket::create([
            'subject' => 'Test ticket',
            'description' => 'Test description.',
            'customer_id' => $customer->id,
            'status_id' => $openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);
    }

    public function test_internal_note_does_not_broadcast_ticket_message_received(): void
    {
        Event::fake([TicketMessageReceived::class, NewTicketMessage::class, MessageAdded::class]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.messages.store', $this->ticket), [
                'body' => 'Cliente recurrente, ya tuvo un retraso el mes pasado.',
                'is_internal' => true,
            ])
            ->assertSuccessful();

        Event::assertNotDispatched(TicketMessageReceived::class);
        Event::assertNotDispatched(NewTicketMessage::class);
        Event::assertDispatched(MessageAdded::class);
    }

    public function test_public_reply_does_not_broadcast_ticket_message_received_either(): void
    {
        // Ambos eventos son código muerto sin consumidor — se retiraron para
        // TODO mensaje, no solo para notas internas. Este test fija ese
        // comportamiento: si algún día se reintroduce el broadcast, debe
        // ser deliberado y sobre un canal autenticado, no un descuido.
        Event::fake([TicketMessageReceived::class, NewTicketMessage::class, MessageAdded::class]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.messages.store', $this->ticket), [
                'body' => 'Su pedido salió de nuestro almacén ayer por la tarde.',
                'is_internal' => false,
            ])
            ->assertSuccessful();

        Event::assertNotDispatched(TicketMessageReceived::class);
        Event::assertNotDispatched(NewTicketMessage::class);
        Event::assertDispatched(MessageAdded::class);
    }
}
