<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketEmailLogPanelRenderer;
use Tests\TestCase;

/**
 * TicketEmailLogPanelRenderer es el lado HelpdeskTickets del punto de
 * extensión EntityPanelRegistry de HelpdeskEmailActivity — ver el docblock de la
 * clase para el diseño completo. Este test cubre las tres ramas de
 * supports()/render() sin pasar por el registro (EntityPanelRegistry) ni
 * por HTTP: instancia el renderer directamente, igual que hace
 * HelpdeskTicketsServiceProvider::registerEmailLogPanel().
 */
class TicketEmailLogPanelRendererTest extends TestCase
{
    use DatabaseTransactions;

    // Ticket/Customer viven en la conexión 'helpdesk'; EmailLog vive en la
    // conexión default ('mysql' en este entorno) — sin 'mysql' aquí, el
    // EmailLog creado en el test no se revertiría al terminar (mismo gotcha
    // ya documentado en FetchTicketEmailsJobTest::$connectionsToTransact).
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TicketEmailLogPanelRenderer $renderer;

    private Customer $customer;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new TicketEmailLogPanelRenderer;
        $this->customer = Customer::factory()->create();
        $this->status = TicketStatus::factory()->create(['name' => 'Abierto-test']);
    }

    private function makeEmailLogFor(Ticket $ticket): EmailLog
    {
        // entity_type se guarda como FQCN completo — confirmado por
        // config/config.php de HelpdeskEmailActivity (entity_routes /
        // entity_labels usan 'Modules\\HelpdeskTickets\\Models\\Ticket' como
        // clave), no el string corto 'Ticket' que trae por defecto
        // EmailLogFactory::definition() (ese default nunca se ejercita en
        // producción para tickets reales).
        return EmailLog::factory()->create([
            'entity_type' => Ticket::class,
            'entity_id' => $ticket->id,
        ]);
    }

    public function test_supports_returns_false_for_a_different_entity_type(): void
    {
        $this->assertFalse($this->renderer->supports(Customer::class));
        $this->assertFalse($this->renderer->supports('Modules\\Helpdesk\\Models\\Conversation'));
    }

    public function test_supports_returns_true_for_ticket_entity_type(): void
    {
        $this->assertTrue($this->renderer->supports(Ticket::class));
    }

    public function test_render_returns_null_when_the_ticket_no_longer_exists(): void
    {
        $emailLog = EmailLog::factory()->create([
            'entity_type' => Ticket::class,
            'entity_id' => 999999,
        ]);

        $this->assertNull($this->renderer->render($emailLog));
    }

    public function test_render_includes_related_tickets_from_the_same_customer(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Ticket del email',
            'description' => 'Descripción.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);

        $otherTicket = Ticket::create([
            'subject' => 'Otro ticket del mismo cliente',
            'description' => 'Descripción.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);

        $emailLog = $this->makeEmailLogFor($ticket);

        $html = $this->renderer->render($emailLog);

        $this->assertNotNull($html);
        $this->assertStringContainsString($otherTicket->ticket_number, $html);
        $this->assertStringContainsString('Otro ticket del mismo cliente', $html);

        // El propio ticket del email no debe listarse como "relacionado" de
        // sí mismo — mismo comportamiento que
        // TicketMailDetailDataController::relatedTickets().
        $this->assertStringNotContainsString('Ticket del email', $html);
    }

    public function test_render_shows_the_empty_state_when_the_customer_has_no_other_tickets(): void
    {
        $ticket = Ticket::create([
            'subject' => 'Único ticket del cliente',
            'description' => 'Descripción.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);

        $emailLog = $this->makeEmailLogFor($ticket);

        $html = $this->renderer->render($emailLog);

        $this->assertNotNull($html);
        $this->assertStringContainsString('Sin otros tickets de este cliente.', $html);
    }
}
