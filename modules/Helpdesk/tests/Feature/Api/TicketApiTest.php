<?php

namespace Modules\Helpdesk\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketCategory;
use Modules\Helpdesk\Models\TicketStatus;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private TicketCategory $category;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->agent = User::factory()->create();

        // Seed the minimum helpdesk data needed for tests
        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );

        $this->category = TicketCategory::firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General', 'is_active' => true]
        );
    }

    // ─── Index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_ticket_list(): void
    {
        $this->createTicket(['subject' => 'First ticket']);
        $this->createTicket(['subject' => 'Second ticket']);

        $response = $this->actingAs($this->agent)
            ->getJson('/api/helpdesk/tickets');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'ticket_number', 'subject', 'priority']],
                'current_page',
                'per_page',
                'total',
            ]);
    }

    public function test_index_can_filter_by_priority(): void
    {
        $this->createTicket(['subject' => 'Urgent ticket', 'priority' => 'urgent']);
        $this->createTicket(['subject' => 'Low ticket', 'priority' => 'low']);

        $response = $this->actingAs($this->agent)
            ->getJson('/api/helpdesk/tickets?priority=urgent');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $ticket) {
            $this->assertEquals('urgent', $ticket['priority']);
        }
    }

    public function test_index_can_search_by_subject(): void
    {
        $this->createTicket(['subject' => 'Login broken completely']);
        $this->createTicket(['subject' => 'Payment issue']);

        $response = $this->actingAs($this->agent)
            ->getJson('/api/helpdesk/tickets?search=Login');

        $response->assertOk();

        $subjects = collect($response->json('data'))->pluck('subject');
        $this->assertTrue($subjects->contains(fn ($s) => str_contains($s, 'Login')));
    }

    // ─── Store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_ticket_with_valid_data(): void
    {
        $response = $this->actingAs($this->agent)
            ->postJson('/api/helpdesk/tickets', [
                'subject' => 'New ticket from API',
                'description' => 'Detailed description of the issue.',
                'category_id' => $this->category->id,
                'priority' => 'normal',
                'customer_email' => 'customer@example.com',
                'customer_name' => 'Test Customer',
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['subject' => 'New ticket from API'])
            ->assertJsonStructure(['id', 'ticket_number', 'subject', 'priority', 'source']);

        $this->assertEquals('api', $response->json('source'));
    }

    public function test_store_returns_422_when_subject_missing(): void
    {
        $response = $this->actingAs($this->agent)
            ->postJson('/api/helpdesk/tickets', [
                'description' => 'Missing subject here.',
                'category_id' => $this->category->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['subject']);
    }

    public function test_store_returns_422_when_description_missing(): void
    {
        $response = $this->actingAs($this->agent)
            ->postJson('/api/helpdesk/tickets', [
                'subject' => 'Subject present',
                'category_id' => $this->category->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    }

    public function test_store_returns_422_when_category_id_missing(): void
    {
        $response = $this->actingAs($this->agent)
            ->postJson('/api/helpdesk/tickets', [
                'subject' => 'Subject present',
                'description' => 'Description present.',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_store_returns_422_on_invalid_priority(): void
    {
        $response = $this->actingAs($this->agent)
            ->postJson('/api/helpdesk/tickets', [
                'subject' => 'Priority test',
                'description' => 'Testing invalid priority.',
                'category_id' => $this->category->id,
                'priority' => 'critical', // not in allowed list
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_store_resolves_existing_customer_by_email(): void
    {
        // First creation — customer is auto-created
        $this->actingAs($this->agent)
            ->postJson('/api/helpdesk/tickets', [
                'subject' => 'First contact',
                'description' => 'First ticket for this customer.',
                'category_id' => $this->category->id,
                'customer_email' => 'repeat@example.com',
            ]);

        // Second creation with same email — reuses same customer
        $response = $this->actingAs($this->agent)
            ->postJson('/api/helpdesk/tickets', [
                'subject' => 'Second contact',
                'description' => 'Second ticket for the same customer.',
                'category_id' => $this->category->id,
                'customer_email' => 'repeat@example.com',
            ]);

        $response->assertCreated();

        $firstCustomerId = Ticket::where('subject', 'First contact')->value('customer_id');
        $secondCustomerId = Ticket::where('subject', 'Second contact')->value('customer_id');
        $this->assertEquals($firstCustomerId, $secondCustomerId);
    }

    // ─── Show ──────────────────────────────────────────────────────────────────

    public function test_show_returns_ticket_by_ticket_number(): void
    {
        $ticket = $this->createTicket(['subject' => 'Show me this ticket']);

        $response = $this->actingAs($this->agent)
            ->getJson("/api/helpdesk/tickets/{$ticket->ticket_number}");

        $response->assertOk()
            ->assertJsonFragment([
                'ticket_number' => $ticket->ticket_number,
                'subject' => 'Show me this ticket',
            ]);
    }

    public function test_show_returns_404_for_unknown_ticket_number(): void
    {
        $response = $this->actingAs($this->agent)
            ->getJson('/api/helpdesk/tickets/TCK-9999-99999');

        $response->assertNotFound();
    }

    // ─── Update ────────────────────────────────────────────────────────────────

    public function test_update_modifies_ticket_fields(): void
    {
        $ticket = $this->createTicket(['subject' => 'Original subject', 'priority' => 'normal']);

        $response = $this->actingAs($this->agent)
            ->putJson("/api/helpdesk/tickets/{$ticket->ticket_number}", [
                'subject' => 'Updated subject',
                'priority' => 'high',
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'subject' => 'Updated subject',
                'priority' => 'high',
            ]);
    }

    public function test_update_returns_422_on_invalid_priority(): void
    {
        $ticket = $this->createTicket();

        $response = $this->actingAs($this->agent)
            ->putJson("/api/helpdesk/tickets/{$ticket->ticket_number}", [
                'priority' => 'extreme',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_update_returns_404_for_unknown_ticket(): void
    {
        $response = $this->actingAs($this->agent)
            ->putJson('/api/helpdesk/tickets/TCK-0000-00000', [
                'subject' => 'Ghost update',
            ]);

        $response->assertNotFound();
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Create a ticket in the helpdesk database.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Default subject',
            'description' => 'Default description.',
            'category_id' => $this->category->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    /**
     * Check whether the helpdesk DB connection is reachable.
     */
    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
