<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class SuggestedArticlesControllerTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private User $agent;

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->agent = User::factory()->create();
        $this->agent->assignRole('super-settings');
        $this->agent->givePermissionTo(['helpdesk.tickets.view']);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'suggested-articles@example.com'],
            ['name' => 'Suggested Articles Customer']
        );
    }

    public function test_endpoint_returns_json_envelope_with_data_array(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.tickets.suggested-articles', $ticket))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_endpoint_degrades_to_empty_when_helpcenter_disabled(): void
    {
        // Con la integración Helpcenter apagada el endpoint no rompe: [] vacío.
        Setting::set('helpcenter.integration_enabled', '0');

        $ticket = $this->createTicket();

        $this->actingAs($this->agent)
            ->getJson(route('manager.helpdesk.tickets.suggested-articles', $ticket))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    }

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'No imprime el ticket',
            'description' => 'La impresora no responde al enviar el documento.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
