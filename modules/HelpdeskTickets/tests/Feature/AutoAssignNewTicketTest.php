<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Listeners\AutoAssignNewTicket;
use Modules\HelpdeskTickets\Mail\TicketAssignedMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAssignment;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Auto-asignación al crear ticket (#78 ve-auto-assign, lado tickets):
 * toggle global, estrategias, disponibilidad/presencia/vacaciones/capacidad,
 * historial y notificación al agente.
 */
class AutoAssignNewTicketTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketStatus $openStatus;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        // No dependas del seeding: crea el rol on-the-fly. Limpia antes la cache
        // de Spatie: el rol de un test anterior se revierte con la transacción
        // pero seguiría cacheado con un id ya inexistente.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('helpdesk-agent', 'web');

        // Estado limpio de los ajustes runtime de auto-asignación (rollback al final).
        Setting::query()->where('key', 'like', 'auto_assign.%')->delete();

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::create([
            'name' => 'Auto-assign Customer',
            'email' => 'autoassign-listener@example.com',
        ]);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function enable(string $strategy = 'round_robin'): void
    {
        config()->set('helpdesk.auto_assignment.enabled', true);
        config()->set('helpdesk.auto_assignment.strategy', $strategy);
    }

    private function createAgent(string $firstname, string $email): User
    {
        $user = User::factory()->create([
            'firstname' => $firstname,
            'lastname' => 'Agent',
            'email' => $email,
        ]);

        $user->assignRole('helpdesk-agent');

        return $user;
    }

    private function makeTicket(?int $assigneeId = null): Ticket
    {
        return Ticket::create([
            'subject' => 'Auto-assign me',
            'description' => 'Ticket for auto-assignment tests.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'assignee_id' => $assigneeId,
        ]);
    }

    private function handle(Ticket $ticket): void
    {
        (new AutoAssignNewTicket(app(AssignmentService::class)))
            ->handle(new TicketCreated($ticket));
    }

    // ─── toggle ──────────────────────────────────────────────────────────────

    public function test_toggle_off_leaves_ticket_unassigned(): void
    {
        config()->set('helpdesk.auto_assignment.enabled', false);
        $this->createAgent('Idle', 'idle-agent@example.com');

        $ticket = $this->makeTicket();
        $this->handle($ticket);

        $this->assertNull($ticket->fresh()->assignee_id);
        $this->assertSame(0, TicketAssignment::where('ticket_id', $ticket->id)->count());
    }

    public function test_runtime_setting_overrides_config_toggle(): void
    {
        // Config off pero setting runtime on (guardado desde el panel #78).
        config()->set('helpdesk.auto_assignment.enabled', false);
        Setting::set('auto_assign.enabled', '1', 'auto_assign');
        Setting::set('auto_assign.strategy', 'round_robin', 'auto_assign');

        $agent = $this->createAgent('Runtime', 'runtime-agent@example.com');

        $ticket = $this->makeTicket();
        $this->handle($ticket);

        $this->assertSame($agent->id, $ticket->fresh()->assignee_id);
    }

    // ─── estrategias ─────────────────────────────────────────────────────────

    public function test_round_robin_assigns_logs_history_and_queues_notification(): void
    {
        Mail::fake();
        $this->enable('round_robin');

        $agent = $this->createAgent('Robin', 'robin-agent@example.com');

        $ticket = $this->makeTicket();
        $this->handle($ticket);

        $this->assertSame($agent->id, $ticket->fresh()->assignee_id);

        // Registro de auditoría (TicketAssignment) sin actor: asignación del sistema.
        $assignment = TicketAssignment::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($assignment);
        $this->assertSame($agent->id, (int) $assignment->assigned_to);
        $this->assertNull($assignment->assigned_by);

        // Historial del ticket.
        $this->assertTrue(
            DB::connection('helpdesk')->table('helpdesk_ticket_histories')
                ->where('ticket_id', $ticket->id)
                ->where('action_type', 'assigned')
                ->exists()
        );

        // Notificación al agente por la vía encolada existente (TicketAssignedMail).
        Mail::assertQueued(TicketAssignedMail::class);
    }

    public function test_workload_strategy_picks_least_loaded_agent(): void
    {
        Mail::fake();
        // 'least_load' (nombre en el panel del core) mapea a workload en tickets.
        $this->enable('least_load');

        $busy = $this->createAgent('Busy', 'busy-agent@example.com');
        $free = $this->createAgent('Free', 'free-agent@example.com');

        for ($i = 0; $i < 2; $i++) {
            $this->makeTicket($busy->id);
        }

        $ticket = $this->makeTicket();
        $this->handle($ticket);

        $this->assertSame($free->id, $ticket->fresh()->assignee_id);
    }

    public function test_manual_strategy_leaves_ticket_unassigned(): void
    {
        $this->enable('manual');
        $this->createAgent('Manual', 'manual-agent@example.com');

        $ticket = $this->makeTicket();
        $this->handle($ticket);

        $this->assertNull($ticket->fresh()->assignee_id);
    }

    // ─── disponibilidad ──────────────────────────────────────────────────────

    public function test_away_agent_is_skipped_and_available_agent_receives_ticket(): void
    {
        Mail::fake();
        $this->enable('round_robin');

        $away = $this->createAgent('Away', 'away-agent@example.com');
        AgentSettings::create([
            'user_id' => $away->id,
            'is_available' => true,
            'accepts_conversations' => 'yes',
            'presence_state' => AgentSettings::PRESENCE_AWAY,
        ]);

        $ticket = $this->makeTicket();
        $this->handle($ticket);
        $this->assertNull($ticket->fresh()->assignee_id);

        $available = $this->createAgent('Present', 'present-agent@example.com');
        AgentSettings::create([
            'user_id' => $available->id,
            'is_available' => true,
            'accepts_conversations' => 'yes',
            'presence_state' => AgentSettings::PRESENCE_AVAILABLE,
        ]);

        $ticket2 = $this->makeTicket();
        $this->handle($ticket2);
        $this->assertSame($available->id, $ticket2->fresh()->assignee_id);
    }

    public function test_agent_on_vacation_is_skipped(): void
    {
        $this->enable('round_robin');

        $vacationing = $this->createAgent('Vacay', 'vacay-agent@example.com');
        AgentSettings::create([
            'user_id' => $vacationing->id,
            'is_available' => true,
            'accepts_conversations' => 'yes',
            'presence_state' => AgentSettings::PRESENCE_AVAILABLE,
            'vacation_until' => now()->addWeek(),
        ]);

        $ticket = $this->makeTicket();
        $this->handle($ticket);

        $this->assertNull($ticket->fresh()->assignee_id);
    }

    public function test_agent_at_capacity_is_skipped(): void
    {
        $this->enable('round_robin');

        $full = $this->createAgent('Full', 'full-agent@example.com');
        AgentSettings::create([
            'user_id' => $full->id,
            'is_available' => true,
            'accepts_conversations' => 'yes',
            'presence_state' => AgentSettings::PRESENCE_AVAILABLE,
            'max_concurrent_conversations' => 1,
            'current_open_count' => 1,
        ]);

        $ticket = $this->makeTicket();
        $this->handle($ticket);

        $this->assertNull($ticket->fresh()->assignee_id);
    }

    // ─── idempotencia y wiring ───────────────────────────────────────────────

    public function test_already_assigned_ticket_is_untouched(): void
    {
        $this->enable('round_robin');

        $owner = $this->createAgent('Owner', 'owner-agent@example.com');
        $this->createAgent('Other', 'other-agent@example.com');

        $ticket = $this->makeTicket($owner->id);
        $this->handle($ticket);

        $this->assertSame($owner->id, $ticket->fresh()->assignee_id);
        $this->assertSame(0, TicketAssignment::where('ticket_id', $ticket->id)->count());
    }

    public function test_listener_is_registered_for_ticket_created(): void
    {
        Event::fake();

        Event::assertListening(TicketCreated::class, AutoAssignNewTicket::class);
    }

    // ─── infra ───────────────────────────────────────────────────────────────

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
