<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

class WidgetTicketsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('helpdesk')->statement('SET FOREIGN_KEY_CHECKS=0');
        DB::connection('helpdesk')->table('helpdesk_ticket_attachments')->truncate();
        DB::connection('helpdesk')->table('helpdesk_ticket_messages')->truncate();
        DB::connection('helpdesk')->table('helpdesk_tickets')->truncate();
        DB::connection('helpdesk')->table('helpdesk_customers')->truncate();
        DB::connection('helpdesk')->table('helpdesk_ticket_categories')->truncate();
        DB::connection('helpdesk')->table('helpdesk_ticket_statuses')->truncate();
        DB::connection('helpdesk')->statement('SET FOREIGN_KEY_CHECKS=1');

        // Ensure a default TicketStatus exists so TicketService can assign it
        TicketStatus::firstOrCreate(
            ['slug' => 'new'],
            [
                'name' => 'Nuevo',
                'color' => '#22c55e',
                'is_open' => true,
                'is_default' => true,
                'is_system' => true,
                'stops_sla_timer' => false,
                'active' => true,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // GET /hd/api/tickets/categories
    // -------------------------------------------------------------------------

    public function test_categories_returns_active_categories_only(): void
    {
        $active = TicketCategory::factory()->create(['active' => true]);
        TicketCategory::factory()->inactive()->create();

        $response = $this->getJson('/hd/api/tickets/categories');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->assertEquals($active->id, $response->json('data.0.id'));
    }

    public function test_categories_returns_empty_when_none_active(): void
    {
        TicketCategory::factory()->inactive()->create();

        $response = $this->getJson('/hd/api/tickets/categories');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_categories_includes_custom_form_fields(): void
    {
        // El endpoint ya no expone el JSON legacy custom_form_fields: sirve los
        // campos estructurados de helpdesk_ticket_category_fields (relación
        // fields() gestionada por el builder de TicketCategoryFieldsController).
        $category = TicketCategory::factory()->create(['active' => true, 'required_fields' => ['serial_number']]);
        $category->fields()->create([
            'type' => 'text',
            'key' => 'serial_number',
            'label' => 'Número de serie',
            'is_required' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/hd/api/tickets/categories');

        $response->assertOk();

        $data = collect($response->json('data'))->firstWhere('id', $category->id);
        $this->assertNotNull($data);
        $this->assertArrayHasKey('fields', $data);
        $this->assertSame('serial_number', $data['fields'][0]['key']);
        $this->assertSame(['serial_number'], $data['required_fields']);
    }

    // -------------------------------------------------------------------------
    // POST /hd/api/tickets
    // -------------------------------------------------------------------------

    public function test_store_creates_ticket_with_valid_data(): void
    {
        Event::fake([TicketCreated::class]);

        $web = Web::factory()->create();

        $response = $this->postJson('/hd/api/tickets', [
            'website_token' => $web->website_token,
            'subject' => 'Test subject',
            'description' => 'Test description for the ticket.',
            'customer_email' => 'visitor@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'ticket_number', 'subject']]);

        $this->assertDatabaseHas('helpdesk_tickets', [
            'subject' => 'Test subject',
            'source' => 'widget',
        ], 'helpdesk');
    }

    public function test_store_returns_422_with_invalid_token(): void
    {
        $response = $this->postJson('/hd/api/tickets', [
            'website_token' => 'invalid-token-that-does-not-exist',
            'subject' => 'Test subject',
            'description' => 'Test description.',
            'customer_email' => 'visitor@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_store_requires_subject_and_description_and_email(): void
    {
        $web = Web::factory()->create();

        $response = $this->postJson('/hd/api/tickets', [
            'website_token' => $web->website_token,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['subject', 'description', 'customer_email']);
    }

    public function test_store_creates_customer_if_not_exists(): void
    {
        Event::fake([TicketCreated::class]);

        $web = Web::factory()->create();
        $newEmail = 'newvisitor@example.com';

        $this->assertDatabaseMissing('helpdesk_customers', ['email' => $newEmail], 'helpdesk');

        $this->postJson('/hd/api/tickets', [
            'website_token' => $web->website_token,
            'subject' => 'Help needed',
            'description' => 'Please assist.',
            'customer_email' => $newEmail,
            'customer_name' => 'New Visitor',
        ])->assertCreated();

        $this->assertDatabaseHas('helpdesk_customers', [
            'email' => $newEmail,
            'name' => 'New Visitor',
        ], 'helpdesk');
    }

    public function test_store_reuses_existing_customer(): void
    {
        Event::fake([TicketCreated::class]);

        $web = Web::factory()->create();
        $customer = Customer::factory()->create(['email' => 'existing@example.com']);

        $this->postJson('/hd/api/tickets', [
            'website_token' => $web->website_token,
            'subject' => 'Another ticket',
            'description' => 'From same customer.',
            'customer_email' => 'existing@example.com',
        ])->assertCreated();

        $this->assertSame(1, Customer::where('email', 'existing@example.com')->count());

        $this->assertDatabaseHas('helpdesk_tickets', [
            'customer_id' => $customer->id,
        ], 'helpdesk');
    }

    public function test_store_dispatches_ticket_created_event(): void
    {
        Event::fake();

        $web = Web::factory()->create();

        $this->postJson('/hd/api/tickets', [
            'website_token' => $web->website_token,
            'subject' => 'Event test',
            'description' => 'Checking event dispatch.',
            'customer_email' => 'events@example.com',
        ])->assertCreated();

        Event::assertDispatched(TicketCreated::class);
    }

    // -------------------------------------------------------------------------
    // GET /hd/api/tickets?email=...&website_token=...
    // -------------------------------------------------------------------------

    // Respuesta neutra: para no ser un oráculo de enumeración de clientes, el
    // endpoint devuelve lo MISMO exista o no el email/tickets (sin has_tickets
    // ni open_count). El detalle real se ve tras autenticarse en el portal.
    public function test_index_returns_neutral_response_for_known_email(): void
    {
        $web = Web::factory()->create();
        $customer = Customer::factory()->create(['email' => 'known@example.com']);
        $status = TicketStatus::where('slug', 'new')->first();

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
        ]);

        $response = $this->getJson('/hd/api/tickets?email=known@example.com&website_token='.$web->website_token);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['login_url', 'message']);
        $this->assertArrayNotHasKey('open_count', $response->json());
        $this->assertArrayNotHasKey('has_tickets', $response->json());
    }

    public function test_index_response_is_identical_for_known_and_unknown_email(): void
    {
        $web = Web::factory()->create();
        $customer = Customer::factory()->create(['email' => 'known@example.com']);
        $status = TicketStatus::where('slug', 'new')->first();
        Ticket::factory()->create(['customer_id' => $customer->id, 'status_id' => $status->id]);

        $known = $this->getJson('/hd/api/tickets?email=known@example.com&website_token='.$web->website_token)->json();
        $unknown = $this->getJson('/hd/api/tickets?email=nobody@example.com&website_token='.$web->website_token)->json();

        // Sin oráculo: idéntica respuesta para email registrado y no registrado.
        $this->assertSame($known, $unknown);
    }

    /**
     * Endpoint publico sin prueba de propiedad del email — no debe filtrar
     * ningun contenido de ticket (subject/prioridad/estado), solo un conteo.
     */
    public function test_index_does_not_leak_ticket_contents(): void
    {
        $web = Web::factory()->create();
        $customer = Customer::factory()->create(['email' => 'known@example.com']);
        $status = TicketStatus::where('slug', 'new')->first();

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'subject' => 'Very confidential subject',
        ]);

        $response = $this->getJson('/hd/api/tickets?email=known@example.com&website_token='.$web->website_token);

        $response->assertOk();
        $this->assertArrayNotHasKey('data', $response->json());
        $response->assertDontSee('Very confidential subject');
    }

    public function test_index_requires_valid_website_token(): void
    {
        $response = $this->getJson('/hd/api/tickets?email=someone@example.com&website_token=bad-token');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_index_requires_email_param(): void
    {
        $web = Web::factory()->create();

        $response = $this->getJson('/hd/api/tickets?website_token='.$web->website_token);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_index_rejects_invalid_email_format(): void
    {
        $web = Web::factory()->create();

        $response = $this->getJson('/hd/api/tickets?email=not-an-email&website_token='.$web->website_token);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

}
