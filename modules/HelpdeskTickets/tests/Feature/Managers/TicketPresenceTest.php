<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Notifications\TicketCollisionNudge;
use Modules\HelpdeskTickets\Services\TicketPresenceService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class TicketPresenceTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private User $agent;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);
        Cache::flush();

        $this->agent = User::factory()->create(['firstname' => 'Agente', 'lastname' => 'Prueba']);
        $this->agent->assignRole('super-settings');
        $this->agent->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        Customer::firstOrCreate(['email' => 'presence@example.com'], ['name' => 'Presence Customer']);
        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_heartbeat_reports_other_active_agents(): void
    {
        $ticket = $this->ticket();
        $other = User::factory()->create(['firstname' => 'Otro', 'lastname' => 'Agente']);

        // Otro agente ya presente.
        app(TicketPresenceService::class)->heartbeat($ticket->id, $other->id, 'Otro Agente', 'replying', now()->timestamp);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.presence.heartbeat', $ticket), ['action' => 'viewing'])
            ->assertOk()
            ->assertJsonCount(1, 'data.viewers')
            ->assertJsonPath('data.viewers.0.user_id', $other->id)
            ->assertJsonPath('data.viewers.0.action', 'replying');
    }

    public function test_leave_removes_the_agent_from_presence(): void
    {
        $ticket = $this->ticket();
        $presence = app(TicketPresenceService::class);
        $presence->heartbeat($ticket->id, $this->agent->id, $this->agent->full_name, 'viewing', now()->timestamp);

        $this->actingAs($this->agent)
            ->deleteJson(route('manager.helpdesk.tickets.presence.leave', $ticket))
            ->assertOk();

        $others = $presence->heartbeat($ticket->id, 99999, 'Alguien', 'viewing', now()->timestamp);
        $this->assertCount(0, $others);
    }

    public function test_stale_viewers_are_pruned(): void
    {
        $ticket = $this->ticket();
        $presence = app(TicketPresenceService::class);

        $t0 = now()->timestamp;
        $presence->heartbeat($ticket->id, 100, 'Viejo', 'viewing', $t0);

        // 40s después: supera el umbral de 35s → debe purgarse.
        $others = $presence->heartbeat($ticket->id, 200, 'Nuevo', 'viewing', $t0 + 40);

        $this->assertCount(0, $others);
    }

    // ─── Modal 23 "Bandeja compartida": avisar a un agente presente ────────────

    public function test_nudge_notifies_the_target_agent(): void
    {
        Notification::fake();

        $ticket = $this->ticket();
        $other = User::factory()->create();
        $other->givePermissionTo('helpdesk.tickets.view');

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.presence.nudge', $ticket), ['to_user_id' => $other->id])
            ->assertOk()
            ->assertJson(['success' => true]);

        Notification::assertSentTo(
            $other,
            TicketCollisionNudge::class,
            fn (TicketCollisionNudge $n) => $n->ticket->is($ticket) && $n->from->is($this->agent)
        );
    }

    public function test_nudge_rejects_avisar_a_uno_mismo(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.presence.nudge', $ticket), ['to_user_id' => $this->agent->id])
            ->assertStatus(422);
    }

    public function test_nudge_rejects_a_un_agente_sin_acceso_al_ticket(): void
    {
        Notification::fake();

        $ticket = $this->ticket();
        $sinAcceso = User::factory()->create(); // sin permiso ni ser el asignado

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.presence.nudge', $ticket), ['to_user_id' => $sinAcceso->id])
            ->assertStatus(422);

        Notification::assertNothingSent();
    }

    public function test_nudge_tiene_cooldown_para_no_spamear_al_mismo_agente(): void
    {
        Notification::fake();

        $ticket = $this->ticket();
        $other = User::factory()->create();
        $other->givePermissionTo('helpdesk.tickets.view');

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.presence.nudge', $ticket), ['to_user_id' => $other->id])
            ->assertOk();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.presence.nudge', $ticket), ['to_user_id' => $other->id])
            ->assertStatus(429);

        Notification::assertSentTimes(TicketCollisionNudge::class, 1);
    }

    private function ticket(): Ticket
    {
        return Ticket::create([
            'subject' => 'Presence ticket',
            'description' => 'x',
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);
    }
}
