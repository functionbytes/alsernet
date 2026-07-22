<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSlaBreach;
use Modules\HelpdeskTickets\Services\EscalationService;
use Tests\TestCase;

/**
 * Cierre del hueco de auditoría en la frontera SLA (ver
 * docs/helpdesk-sla-boundary.md): el escalado de tickets por SLA vencido
 * ahora registra también el incumplimiento en helpdesk_ticket_sla_breaches,
 * de forma idempotente y sin registrar tickets solo "próximos a vencer".
 */
class EscalationSlaBreachAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config([
            'helpdesk.escalation.enabled' => true,
            'helpdesktickets.escalation.sla_enabled' => true,
            'helpdesktickets.escalation.notify_managers' => false,
        ]);
    }

    private function makeSlaTicket(array $overrides = []): Ticket
    {
        return Ticket::factory()->create(array_merge([
            'priority' => 'normal',
            'created_at' => now()->subHours(2), // por debajo del umbral por edad (24h)
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => true,
            'assignee_id' => null,
        ], $overrides));
    }

    public function test_sla_escalation_records_breach_in_ticket_sla_breaches(): void
    {
        $ticket = $this->makeSlaTicket();

        $count = app(EscalationService::class)->checkAndEscalate();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertSame('high', $ticket->refresh()->priority);

        $breach = TicketSlaBreach::query()
            ->where('ticket_id', $ticket->id)
            ->where('breach_type', 'resolution')
            ->first();

        $this->assertNotNull($breach, 'La escalada por SLA debe dejar rastro en helpdesk_ticket_sla_breaches.');
        $this->assertFalse((bool) $breach->resolved);
        $this->assertNotNull($breach->due_at);
        $this->assertGreaterThanOrEqual(1, (int) $breach->breach_duration_minutes);
        $this->assertSame('escalation', $breach->metadata['recorded_by'] ?? null);
    }

    public function test_repeated_sla_escalations_do_not_duplicate_unresolved_breach(): void
    {
        // Sin cooldown, un segundo run vuelve a escalar el mismo ticket; el
        // breach de resolución sin resolver no debe duplicarse.
        config(['helpdesktickets.escalation.cooldown_hours' => 0]);

        $ticket = $this->makeSlaTicket();

        $service = app(EscalationService::class);
        $service->checkAndEscalate();
        $service->checkAndEscalate();

        $this->assertSame(
            1,
            TicketSlaBreach::query()
                ->where('ticket_id', $ticket->id)
                ->where('breach_type', 'resolution')
                ->count()
        );
    }

    public function test_escalation_for_sla_due_soon_but_not_breached_records_no_breach(): void
    {
        // Próximo a vencer (dentro de la ventana de 60 min) pero NO vencido:
        // se escala, pero no es un incumplimiento y no debe auditarse como tal.
        $ticket = $this->makeSlaTicket([
            'sla_resolution_due_at' => now()->addMinutes(30),
            'sla_resolution_breached' => false,
        ]);

        app(EscalationService::class)->checkAndEscalate();

        $this->assertSame('high', $ticket->refresh()->priority);
        $this->assertSame(
            0,
            TicketSlaBreach::query()->where('ticket_id', $ticket->id)->count()
        );
    }
}
