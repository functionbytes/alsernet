<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketTimeEntry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketMergeTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('super-admin');
        $this->manager->givePermissionTo(['helpdesk.tickets.update', 'helpdesk.tickets.view']);

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

    /**
     * Fix de lógica de negocio del 14-sep-2026 (auditoría): close() crea su
     * propio TicketItem ('closed'/'Ticket cerrado') sobre el ticket que
     * recibe la llamada. Llamarlo DESPUÉS de reapuntar items() al destino
     * (como estaba) dejaba ese aviso colgado del ticket origen, que se borra
     * dos líneas después — invisible para siempre en el hilo del destino.
     */
    public function test_merge_moves_the_closed_system_item_to_the_target_ticket(): void
    {
        $source = $this->createTicket(['subject' => 'Source ticket']);
        $target = $this->createTicket(['subject' => 'Target ticket']);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.merge', $source), [
                'merge_into_id' => $target->id,
            ]);

        $this->assertDatabaseHas('helpdesk_ticket_items', [
            'ticket_id' => $target->id,
            'type' => 'closed',
            'body' => 'Ticket cerrado',
        ], 'helpdesk');

        $this->assertDatabaseMissing('helpdesk_ticket_items', [
            'ticket_id' => $source->id,
            'type' => 'closed',
        ], 'helpdesk');
    }

    /**
     * Fix de lógica de negocio del 14-sep-2026 (auditoría): los
     * TicketWatcher del origen se copiaban al destino pero nunca se
     * borraban del origen — quedaban huérfanos apuntando a un ticket_id que
     * deja de existir.
     */
    public function test_merge_does_not_leave_orphaned_watchers_on_the_source_ticket(): void
    {
        $source = $this->createTicket(['subject' => 'Source ticket']);
        $target = $this->createTicket(['subject' => 'Target ticket']);

        $source->watchers()->create(['user_id' => $this->manager->id]);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.merge', $source), [
                'merge_into_id' => $target->id,
            ]);

        $this->assertDatabaseHas('helpdesk_ticket_watchers', [
            'ticket_id' => $target->id,
            'user_id' => $this->manager->id,
        ], 'helpdesk');

        $this->assertDatabaseMissing('helpdesk_ticket_watchers', [
            'ticket_id' => $source->id,
        ], 'helpdesk');
    }

    public function test_merge_migrates_related_data_to_target_ticket(): void
    {
        $source = $this->createTicket(['subject' => 'Source ticket']);
        $target = $this->createTicket(['subject' => 'Target ticket']);
        $other = $this->createTicket(['subject' => 'Other ticket']);

        $note = TicketNote::create([
            'ticket_id' => $source->id,
            'user_id' => $this->manager->id,
            'body' => 'Internal note on source',
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $source->id,
            'user_id' => $this->manager->id,
            'body' => 'Comment on source',
        ]);

        $timeEntry = TicketTimeEntry::create([
            'ticket_id' => $source->id,
            'user_id' => $this->manager->id,
            'minutes' => 30,
            'logged_at' => now(),
        ]);

        $mail = TicketMail::create([
            'ticket_id' => $source->id,
            'direction' => 'inbound',
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'subject' => 'Mail on source',
        ]);

        // Link source → other must be repointed; link source → target must be
        // dropped (it would become a self-link on the target).
        TicketLink::create([
            'ticket_id' => $source->id,
            'linked_ticket_id' => $other->id,
            'link_type' => 'related',
        ]);
        TicketLink::create([
            'ticket_id' => $source->id,
            'linked_ticket_id' => $target->id,
            'link_type' => 'related',
        ]);

        $historyIds = $source->history()->pluck('id')->all();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.merge', $source), [
                'merge_into_id' => $target->id,
            ])
            ->assertRedirect(route('manager.helpdesk.tickets.show', $target));

        $this->assertSame($target->id, $note->fresh()->ticket_id);
        $this->assertSame($target->id, $comment->fresh()->ticket_id);
        $this->assertSame($target->id, $timeEntry->fresh()->ticket_id);
        $this->assertSame($target->id, $mail->fresh()->ticket_id);

        // Pre-merge history rows now belong to the target ticket.
        if ($historyIds !== []) {
            $this->assertSame(
                count($historyIds),
                DB::connection('helpdesk')->table('helpdesk_ticket_histories')
                    ->whereIn('id', $historyIds)
                    ->where('ticket_id', $target->id)
                    ->count()
            );
        }

        $this->assertDatabaseHas('helpdesk_ticket_links', [
            'ticket_id' => $target->id,
            'linked_ticket_id' => $other->id,
        ], 'helpdesk');

        // No self-link and nothing left pointing at the deleted source.
        $this->assertDatabaseMissing('helpdesk_ticket_links', [
            'ticket_id' => $target->id,
            'linked_ticket_id' => $target->id,
        ], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_ticket_links', [
            'ticket_id' => $source->id,
        ], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_ticket_links', [
            'linked_ticket_id' => $source->id,
        ], 'helpdesk');
    }

    public function test_merge_does_not_duplicate_existing_links_on_target(): void
    {
        $source = $this->createTicket(['subject' => 'Source ticket']);
        $target = $this->createTicket(['subject' => 'Target ticket']);
        $other = $this->createTicket(['subject' => 'Other ticket']);

        TicketLink::create([
            'ticket_id' => $source->id,
            'linked_ticket_id' => $other->id,
            'link_type' => 'related',
        ]);
        TicketLink::create([
            'ticket_id' => $target->id,
            'linked_ticket_id' => $other->id,
            'link_type' => 'related',
        ]);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.tickets.merge', $source), [
                'merge_into_id' => $target->id,
            ]);

        $this->assertSame(
            1,
            TicketLink::where('ticket_id', $target->id)
                ->where('linked_ticket_id', $other->id)
                ->count()
        );
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
