<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Console;

use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * ticket:autoclose — 24-sep-2026: el estado resuelto se buscaba con
 * LIKE '%resolv%' (no casa con "Resuelto") y el cerrado era el primero con
 * is_open=false por orden ("En Espera"). Resultado: cualquier ticket
 * inactivo, en el estado que fuera, acababa movido a "En Espera".
 */
class AutoCloseTicketsCommandTest extends TestCase
{
    use SharesHelpdeskPdo;

    public function test_solo_cierra_los_resueltos_inactivos_y_los_pasa_a_cerrado(): void
    {
        // Sin esto, cada ticket real que cumpla la condición mandaría el
        // aviso de cambio de estado a su cliente.
        Event::fake([TicketStatusChanged::class]);

        $resolved = $this->estado('resolved', 'Resuelto', false, 6);
        $onHold = $this->estado('on-hold', 'En Espera', false, 4);
        $open = $this->estado('open', 'Abierto', true, 2);
        $closed = $this->estado('closed', 'Cerrado', false, 8);

        $resueltoViejo = $this->ticketInactivo($resolved, 40);
        $abiertoViejo = $this->ticketInactivo($open, 40);
        $resueltoReciente = $this->ticketInactivo($resolved, 5);

        $this->artisan('ticket:autoclose', ['--days' => 30])->assertSuccessful();

        $this->assertSame($closed->id, $resueltoViejo->fresh()->status_id);
        $this->assertNotNull($resueltoViejo->fresh()->closed_at);

        $this->assertSame($open->id, $abiertoViejo->fresh()->status_id, 'Un ticket abierto no debe cerrarse por inactividad.');
        $this->assertNull($abiertoViejo->fresh()->closed_at);
        $this->assertNotSame($onHold->id, $abiertoViejo->fresh()->status_id);

        $this->assertSame($resolved->id, $resueltoReciente->fresh()->status_id);
    }

    private function estado(string $slug, string $name, bool $isOpen, int $order): TicketStatus
    {
        return TicketStatus::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'color' => '#90bb13', 'is_open' => $isOpen, 'is_default' => false, 'order' => $order]
        );
    }

    private function ticketInactivo(TicketStatus $status, int $dias): Ticket
    {
        $customer = Customer::firstOrCreate(
            ['email' => 'autoclose-test@example.com'],
            ['name' => 'AutoClose Test']
        );

        $ticket = Ticket::create([
            'subject' => 'AutoClose '.uniqid(),
            'description' => 'x',
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);

        Ticket::withoutTimestamps(fn () => $ticket->forceFill(['updated_at' => now()->subDays($dias)])->save());

        return $ticket;
    }
}
