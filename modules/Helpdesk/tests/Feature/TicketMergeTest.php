<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketStatus;
use Tests\TestCase;

class TicketMergeTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->manager = User::factory()->create();

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
    }

    public function test_manager_can_merge_ticket_into_another(): void
    {
        $source = $this->createTicket(['subject' => 'Source ticket']);
        $target = $this->createTicket(['subject' => 'Target ticket']);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.merge', $source), [
                'merge_into_id' => $target->id,
            ])
            ->assertRedirect(route('manager.helpdesk.tickets.show', $target));
    }

    public function test_merge_moves_messages_to_target_ticket(): void
    {
        $source = $this->createTicket(['subject' => 'Source ticket']);
        $target = $this->createTicket(['subject' => 'Target ticket']);

        $source->items()->create([
            'type' => 'message',
            'body' => 'Original message on source',
            'is_internal' => false,
        ]);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.merge', $source), [
                'merge_into_id' => $target->id,
            ]);

        $this->assertDatabaseMissing('helpdesk_ticket_items', [
            'ticket_id' => $source->id,
            'body' => 'Original message on source',
        ], 'helpdesk');

        $this->assertDatabaseHas('helpdesk_ticket_items', [
            'ticket_id' => $target->id,
            'body' => 'Original message on source',
        ], 'helpdesk');
    }

    public function test_cannot_merge_ticket_into_itself(): void
    {
        $ticket = $this->createTicket(['subject' => 'Solo ticket']);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.merge', $ticket), [
                'merge_into_id' => $ticket->id,
            ])
            ->assertSessionHasErrors(['merge_into_id']);
    }

    public function test_merge_closes_source_ticket(): void
    {
        $source = $this->createTicket(['subject' => 'Source ticket']);
        $target = $this->createTicket(['subject' => 'Target ticket']);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.merge', $source), [
                'merge_into_id' => $target->id,
            ]);

        $this->assertSoftDeleted('helpdesk_tickets', ['id' => $source->id], 'helpdesk');
    }

    public function test_merge_requires_authentication(): void
    {
        $source = $this->createTicket();
        $target = $this->createTicket();

        $this->post(route('manager.helpdesk.tickets.merge', $source), [
            'merge_into_id' => $target->id,
        ])->assertRedirect('/login');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Test ticket',
            'description' => 'Test description.',
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

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
