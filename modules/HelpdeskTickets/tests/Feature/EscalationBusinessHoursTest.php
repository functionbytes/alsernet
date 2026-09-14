<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\EscalationService;
use Tests\TestCase;

/**
 * Escalado por antigüedad en horas HÁBILES (helpdesktickets.escalation.
 * business_hours, OFF por defecto). Con el toggle activo, los umbrales por
 * prioridad se evalúan con el calendario de HelpdeskSla
 * (BusinessHoursCalculator); apagado, el comportamiento en horas naturales es
 * exactamente el histórico (cubierto además por EscalateTicketsJobTest).
 */
class EscalationBusinessHoursTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'helpdesk.escalation.enabled' => true,
            'helpdesk.escalation.thresholds.low' => 48,
            'helpdesk.escalation.thresholds.normal' => 24,
            'helpdesk.escalation.thresholds.high' => 12,
            'helpdesktickets.escalation.sla_enabled' => true,
            'helpdesktickets.escalation.cooldown_hours' => 24,
            'helpdesktickets.escalation.max_escalations' => 3,
            'helpdesktickets.escalation.notify_managers' => false,
            'helpdesksla.default_business_hours.timezone' => 'Europe/Madrid',
        ]);

        // Calendario determinista Lun-Vie 09:00-18:00 (Europe/Madrid),
        // pre-sembrado en la misma clave que cachea BusinessHoursCalculator
        // para no depender de filas reales en helpdesk_business_hours.
        Cache::put(BusinessHoursCalculator::CACHE_KEY, [
            1 => ['open' => '09:00', 'close' => '18:00'],
            2 => ['open' => '09:00', 'close' => '18:00'],
            3 => ['open' => '09:00', 'close' => '18:00'],
            4 => ['open' => '09:00', 'close' => '18:00'],
            5 => ['open' => '09:00', 'close' => '18:00'],
        ], 300);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'bh-escalation-customer@example.com'],
            ['name' => 'BH Escalation Customer']
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::forget(BusinessHoursCalculator::CACHE_KEY);

        parent::tearDown();
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        $ticket = Ticket::create(array_merge([
            'subject' => 'BH escalation test ticket',
            'description' => 'BH escalation test description',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ], $overrides));

        return $ticket;
    }

    public function test_business_hours_mode_does_not_escalate_over_the_weekend(): void
    {
        config(['helpdesktickets.escalation.business_hours' => true]);

        // Sábado 12:00 UTC. Ticket creado el viernes a las 06:00 UTC (08:00
        // Madrid): 30 horas naturales (> umbral de 24) pero solo ~9 horas
        // hábiles transcurridas (viernes 09-18), así que NO debe escalar.
        Carbon::setTestNow(Carbon::parse('2026-07-25 12:00:00', 'UTC'));

        $ticket = $this->makeTicket();
        $ticket->forceFill(['created_at' => now()->subHours(30)])->saveQuietly();

        app(EscalationService::class)->checkAndEscalate();

        $ticket->refresh();

        $this->assertSame('normal', $ticket->priority, 'En horas hábiles el umbral de 24h no se alcanza durante el fin de semana.');
        $this->assertSame(0, $ticket->escalation_count);
        $this->assertNull($ticket->escalated_at);
    }

    public function test_natural_hours_mode_escalates_the_same_ticket(): void
    {
        config(['helpdesktickets.escalation.business_hours' => false]);

        Carbon::setTestNow(Carbon::parse('2026-07-25 12:00:00', 'UTC'));

        $ticket = $this->makeTicket();
        $ticket->forceFill(['created_at' => now()->subHours(30)])->saveQuietly();

        app(EscalationService::class)->checkAndEscalate();

        $ticket->refresh();

        $this->assertSame('high', $ticket->priority, 'Con el toggle apagado (default) rigen las horas naturales, como siempre.');
        $this->assertSame(1, $ticket->escalation_count);
    }

    public function test_business_hours_mode_still_escalates_a_genuinely_old_ticket(): void
    {
        config(['helpdesktickets.escalation.business_hours' => true]);

        Carbon::setTestNow(Carbon::parse('2026-07-25 12:00:00', 'UTC'));

        // Creado 10 días antes: acumula de sobra 24 horas hábiles.
        $ticket = $this->makeTicket();
        $ticket->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();

        app(EscalationService::class)->checkAndEscalate();

        $this->assertSame('high', $ticket->refresh()->priority);
    }

    public function test_business_hours_mode_does_not_affect_sla_based_escalation(): void
    {
        config(['helpdesktickets.escalation.business_hours' => true]);

        Carbon::setTestNow(Carbon::parse('2026-07-25 12:00:00', 'UTC'));

        // El pase 2 (SLA de resolución vencido) evalúa fechas ya calculadas
        // por la política, no umbrales de antigüedad: escala igual en festivo.
        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => now()->subHour(),
            'sla_resolution_breached' => true,
        ]);

        app(EscalationService::class)->checkAndEscalate();

        $this->assertSame('high', $ticket->refresh()->priority);
    }
}
