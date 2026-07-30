<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Jobs;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Jobs\EscalateTicketsJob;
use Modules\HelpdeskTickets\Mail\TicketEscalatedMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\EscalationService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EscalateTicketsJobTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        config([
            'helpdesk.escalation.enabled' => true,
            'helpdesk.escalation.thresholds.low' => 48,
            'helpdesk.escalation.thresholds.normal' => 24,
            'helpdesk.escalation.thresholds.high' => 12,
            'helpdesktickets.escalation.sla_enabled' => true,
            'helpdesktickets.escalation.sla_due_within_minutes' => 60,
            'helpdesktickets.escalation.cooldown_hours' => 24,
            'helpdesktickets.escalation.max_escalations' => 3,
            'helpdesktickets.escalation.notify_managers' => true,
            'helpdesktickets.escalation.notify_roles' => ['manager'],
        ]);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'escalation-customer@example.com'],
            ['name' => 'Escalation Customer']
        );
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        $ticket = Ticket::create(array_merge([
            'subject' => 'Escalation test ticket',
            'description' => 'Escalation test description',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ], $overrides));

        return $ticket;
    }

    private function runJob(): void
    {
        (new EscalateTicketsJob)->handle(app(EscalationService::class));
    }

    public function test_escalates_ticket_past_priority_age_threshold(): void
    {
        Mail::fake();

        $ticket = $this->makeTicket();
        $ticket->forceFill(['created_at' => now()->subHours(30)])->saveQuietly();

        $this->runJob();

        $ticket->refresh();

        $this->assertSame('high', $ticket->priority);
        $this->assertSame(1, $ticket->escalation_count);
        $this->assertNotNull($ticket->escalated_at);
    }

    public function test_does_not_escalate_recent_ticket_without_sla(): void
    {
        Mail::fake();

        $ticket = $this->makeTicket();

        $this->runJob();

        $ticket->refresh();

        $this->assertSame('normal', $ticket->priority);
        $this->assertSame(0, $ticket->escalation_count);
        $this->assertNull($ticket->escalated_at);
    }

    public function test_escalates_ticket_with_overdue_sla(): void
    {
        Mail::fake();

        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => true,
        ]);

        $this->runJob();

        $ticket->refresh();

        $this->assertSame('high', $ticket->priority);
        $this->assertSame(1, $ticket->escalation_count);
    }

    public function test_escalates_ticket_with_sla_about_to_breach(): void
    {
        Mail::fake();

        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => now()->addMinutes(30),
            'sla_resolution_breached' => false,
        ]);

        $this->runJob();

        $ticket->refresh();

        $this->assertSame('high', $ticket->priority);
    }

    public function test_does_not_escalate_when_sla_due_far_in_future(): void
    {
        Mail::fake();

        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => now()->addHours(10),
            'sla_resolution_breached' => false,
        ]);

        $this->runJob();

        $ticket->refresh();

        $this->assertSame('normal', $ticket->priority);
    }

    public function test_records_escalation_in_ticket_history(): void
    {
        Mail::fake();

        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => true,
        ]);

        $this->runJob();

        $this->assertDatabaseHas('helpdesk_ticket_histories', [
            'ticket_id' => $ticket->id,
            'action_type' => 'escalated',
        ], 'helpdesk');

        $history = $ticket->history()->where('action_type', 'escalated')->first();

        $this->assertNotNull($history);
        $this->assertSame('normal', $history->metadata['old_priority']);
        $this->assertSame('high', $history->metadata['new_priority']);
        $this->assertSame(EscalationService::REASON_SLA, $history->metadata['reason']);
    }

    public function test_respects_cooldown_between_escalations(): void
    {
        Mail::fake();

        $ticket = $this->makeTicket([
            'escalated_at' => now()->subHour(),
            'escalation_count' => 1,
        ]);
        $ticket->forceFill(['created_at' => now()->subHours(100)])->saveQuietly();

        $this->runJob();

        $ticket->refresh();

        $this->assertSame('normal', $ticket->priority);
        $this->assertSame(1, $ticket->escalation_count);
    }

    public function test_reescalates_after_cooldown_expires(): void
    {
        Mail::fake();

        $ticket = $this->makeTicket([
            'escalated_at' => now()->subHours(48),
            'escalation_count' => 1,
        ]);
        $ticket->forceFill(['created_at' => now()->subHours(100)])->saveQuietly();

        $this->runJob();

        $ticket->refresh();

        $this->assertSame('high', $ticket->priority);
        $this->assertSame(2, $ticket->escalation_count);
    }

    public function test_respects_max_escalations_cap(): void
    {
        Mail::fake();

        $ticket = $this->makeTicket([
            'escalated_at' => now()->subHours(48),
            'escalation_count' => 3,
        ]);
        $ticket->forceFill(['created_at' => now()->subHours(200)])->saveQuietly();

        $this->runJob();

        $ticket->refresh();

        $this->assertSame('normal', $ticket->priority);
        $this->assertSame(3, $ticket->escalation_count);
    }

    public function test_skips_closed_tickets(): void
    {
        Mail::fake();

        $ticket = $this->makeTicket([
            'closed_at' => now()->subHour(),
            'sla_resolution_due_at' => now()->subHours(2),
            'sla_resolution_breached' => true,
        ]);
        $ticket->forceFill(['created_at' => now()->subHours(100)])->saveQuietly();

        $this->runJob();

        $ticket->refresh();

        $this->assertSame('normal', $ticket->priority);
    }

    public function test_skips_everything_when_escalation_disabled(): void
    {
        Mail::fake();

        config(['helpdesk.escalation.enabled' => false]);

        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => true,
        ]);
        $ticket->forceFill(['created_at' => now()->subHours(100)])->saveQuietly();

        $this->runJob();

        $ticket->refresh();

        $this->assertSame('normal', $ticket->priority);
        Mail::assertNothingQueued();
    }

    public function test_queues_notification_for_assignee_and_managers(): void
    {
        Mail::fake();

        Role::findOrCreate('manager', 'web');

        $agent = User::factory()->create();
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $ticket = $this->makeTicket([
            'assignee_id' => $agent->id,
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => true,
        ]);

        $this->runJob();

        Mail::assertQueued(TicketEscalatedMail::class, fn ($mail) => $mail->hasTo($agent->email));
        Mail::assertQueued(TicketEscalatedMail::class, fn ($mail) => $mail->hasTo($manager->email));
    }

    public function test_notifies_managers_even_without_assignee(): void
    {
        Mail::fake();

        Role::findOrCreate('manager', 'web');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => true,
        ]);

        $this->runJob();

        Mail::assertQueued(TicketEscalatedMail::class, fn ($mail) => $mail->hasTo($manager->email));
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
