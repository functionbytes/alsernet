<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Fix de lógica de negocio del 14-sep-2026 (auditoría): TicketUpdateService
 * dispara TicketStatusChanged Y TicketUpdated para la MISMA operación cuando
 * cambia status_id — ambos eventos tienen un listener que llama a
 * AutomationEngine::handle('ticket.updated', ...), así que toda
 * automatización con ese trigger corría DOS VECES por un solo cambio de
 * estado real (un 'close' reenviaba la encuesta CSAT al cliente por
 * duplicado, 'notify_agent' notificaba dos veces, run_count se duplicaba).
 *
 * Este test reproduce el flujo HTTP real (no AutomationEngine::handle()
 * directo, que no ejercita el doble-disparo) y fija que run_count sube
 * EXACTAMENTE 1 por un PUT que cambia el estado.
 */
class RunAutomationsOnTicketUpdatedTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    public function test_status_change_via_update_runs_the_automation_only_once(): void
    {
        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $manager = User::factory()->create();
        $manager->assignRole('super-settings');
        $manager->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $pendingStatus = TicketStatus::firstOrCreate(
            ['slug' => 'pending'],
            ['name' => 'Pending', 'color' => '#f0ad4e', 'is_open' => true, 'is_default' => false, 'order' => 2]
        );

        $customer = Customer::firstOrCreate(
            ['email' => 'run-automations-test@example.com'],
            ['name' => 'Run Automations Test']
        );

        $ticket = Ticket::create([
            'subject' => 'Test ticket',
            'description' => 'x',
            'customer_id' => $customer->id,
            'status_id' => $openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);

        $automation = Automation::create([
            'name' => 'Cualquier actualización',
            'trigger_event' => 'ticket.updated',
            'conditions' => [],
            'actions' => [['type' => 'add_tag', 'value' => 'tocado-por-automatizacion']],
            'is_active' => true,
            'order' => 1,
        ]);

        $this->actingAs($manager)
            ->putJson(route('manager.helpdesk.tickets.update', $ticket), [
                'status_id' => $pendingStatus->id,
            ])
            ->assertOk();

        $this->assertSame(
            1,
            $automation->fresh()->run_count,
            'La automatización debe correr UNA sola vez por un único cambio de estado, no dos.'
        );
    }
}
