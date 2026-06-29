<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketTimeEntry;
use Tests\TestCase;

class TimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private Ticket $ticket;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->agent = User::factory()->create();

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

        $this->ticket = Ticket::create([
            'subject' => 'Ticket for time tracking',
            'description' => 'Test description.',
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);
    }

    public function test_agent_can_log_time_to_ticket(): void
    {
        $response = $this->actingAs($this->agent)
            ->postJson(
                route('manager.helpdesk.tickets.time-entries.store', $this->ticket),
                [
                    'minutes' => 90,
                    'description' => 'Investigated the issue',
                ]
            );

        $response->assertCreated()
            ->assertJsonFragment(['minutes' => 90])
            ->assertJsonStructure(['id', 'minutes', 'formatted_duration', 'user']);
    }

    public function test_minutes_validation_fails_above_480(): void
    {
        $response = $this->actingAs($this->agent)
            ->postJson(
                route('manager.helpdesk.tickets.time-entries.store', $this->ticket),
                ['minutes' => 500]
            );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['minutes']);
    }

    public function test_minutes_validation_fails_below_1(): void
    {
        $response = $this->actingAs($this->agent)
            ->postJson(
                route('manager.helpdesk.tickets.time-entries.store', $this->ticket),
                ['minutes' => 0]
            );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['minutes']);
    }

    public function test_owner_can_delete_own_time_entry(): void
    {
        $entry = TicketTimeEntry::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent->id,
            'minutes' => 30,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->agent)
            ->deleteJson(
                route('manager.helpdesk.tickets.time-entries.destroy', [$this->ticket, $entry])
            );

        $response->assertNoContent();
        $this->assertDatabaseMissing('helpdesk_ticket_time_entries', ['id' => $entry->id], 'helpdesk');
    }

    public function test_other_user_cannot_delete_time_entry(): void
    {
        $otherUser = User::factory()->create();

        $entry = TicketTimeEntry::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent->id,
            'minutes' => 45,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($otherUser)
            ->deleteJson(
                route('manager.helpdesk.tickets.time-entries.destroy', [$this->ticket, $entry])
            );

        $response->assertForbidden();
        $this->assertDatabaseHas('helpdesk_ticket_time_entries', ['id' => $entry->id], 'helpdesk');
    }

    public function test_time_entries_listed_with_formatted_duration(): void
    {
        TicketTimeEntry::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent->id,
            'minutes' => 150,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->agent)
            ->getJson(
                route('manager.helpdesk.tickets.time-entries.index', $this->ticket)
            );

        $response->assertOk()
            ->assertJsonStructure([
                'entries' => [['id', 'minutes', 'formatted_duration']],
                'total_minutes',
                'formatted_total',
            ]);

        $entry = collect($response->json('entries'))->first();
        $this->assertEquals('2h 30m', $entry['formatted_duration']);
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson(
            route('manager.helpdesk.tickets.time-entries.store', $this->ticket),
            ['minutes' => 60]
        )->assertUnauthorized();
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

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
