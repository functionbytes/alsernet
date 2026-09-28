<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Bug real (28-sep-2026): Api\TicketsController::store() creaba el ticket
 * sin status_id — a diferencia del resto de vías de alta (Portal,
 * InboundEmailTicketResolver, HelpdeskTicketBridgeService), que sí fijan un
 * fallback explícito. En local, esto dejaba tickets con status_id NULL en
 * cuanto el catálogo se quedaba sin ningún is_default=true.
 */
class TicketsControllerStoreStatusTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();

        // Ningún status es_default: reproduce el caso real que dejaba el
        // catálogo sin fallback.
        Cache::forget('helpdesk:catalogs:default-status');
    }

    public function test_store_assigns_the_default_status_when_the_catalog_has_one(): void
    {
        TicketStatus::where('is_default', true)->update(['is_default' => false]);
        $default = TicketStatus::firstOrCreate(
            ['slug' => 'new'],
            ['name' => 'Nuevo', 'color' => '#6C757D', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $default->forceFill(['is_default' => true])->save();
        Cache::forget('helpdesk:catalogs:default-status');

        $category = TicketCategory::create(['name' => 'Soporte API '.uniqid(), 'active' => true]);
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.tickets.create');
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson(route('api.v1.helpdesk.tickets.store'), [
            'subject' => 'Ticket creado por API',
            'description' => 'Cuerpo del ticket.',
            'category_id' => $category->id,
        ])->assertCreated();

        $ticketId = $response->json('data.id') ?? $response->json('data.data.id');
        $ticket = $ticketId ? Ticket::find($ticketId) : Ticket::where('subject', 'Ticket creado por API')->latest()->first();

        $this->assertNotNull($ticket);
        $this->assertSame($default->id, $ticket->status_id);
    }

    public function test_store_falls_back_to_the_first_open_status_when_the_catalog_has_no_default(): void
    {
        TicketStatus::where('is_default', true)->update(['is_default' => false]);
        $openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Abierto', 'color' => '#0D6EFD', 'is_open' => true, 'is_default' => false, 'order' => 1]
        );
        $openStatus->forceFill(['is_default' => false, 'is_open' => true])->save();
        Cache::forget('helpdesk:catalogs:default-status');

        $category = TicketCategory::create(['name' => 'Soporte API '.uniqid(), 'active' => true]);
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.tickets.create');
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson(route('api.v1.helpdesk.tickets.store'), [
            'subject' => 'Ticket sin default en catalogo',
            'description' => 'Cuerpo del ticket.',
            'category_id' => $category->id,
        ])->assertCreated();

        $ticket = Ticket::where('subject', 'Ticket sin default en catalogo')->latest()->first();

        $this->assertNotNull($ticket);
        $this->assertNotNull($ticket->status_id, 'El ticket no debe quedar con status_id NULL.');
    }
}
