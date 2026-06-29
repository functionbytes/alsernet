<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase;

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
        $category = TicketCategory::factory()->withCustomFields()->create(['active' => true]);

        $response = $this->getJson('/hd/api/tickets/categories');

        $response->assertOk();

        $data = $response->json('data.0');
        $this->assertArrayHasKey('custom_form_fields', $data);
        $this->assertNotNull($data['custom_form_fields']);
        $this->assertEquals($category->id, $data['id']);
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

    public function test_index_returns_tickets_for_known_email(): void
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
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_index_returns_empty_for_unknown_email(): void
    {
        $web = Web::factory()->create();

        $response = $this->getJson('/hd/api/tickets?email=nobody@example.com&website_token='.$web->website_token);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
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
