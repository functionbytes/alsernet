<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketChannelMailerService;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * threadSubject() en un archivo aparte de TicketChannelMailerServiceTest a
 * propósito: ESE archivo toca TicketEmailChannelsRepository/incoming_email
 * (Setting real, ver feedback_settings_tests_corrupted_real_channel_data) y
 * está en la lista de tests que no se vuelven a correr esta sesión. Aquí el
 * repositorio se instancia pero nunca se llama a ninguno de sus métodos
 * (all/create/default/setDefault) — cero contacto con esa Setting.
 *
 * Bug real que motiva esto (4-sep-2026, TCK-2026-00093, correo real): la
 * confirmación usa un asunto fijo de plantilla ("Hemos recibido tu solicitud
 * — #TCK-...") pero las respuestas automáticas posteriores construían el
 * suyo desde ticket.subject ("Contacto general") — con In-Reply-To/References
 * correctos pero el asunto distinto, Gmail abría un hilo nuevo en cada aviso.
 */
class TicketChannelMailerServiceThreadSubjectTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketChannelMailerService $service;

    private TicketStatus $status;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->service = new TicketChannelMailerService(new TicketEmailChannelsRepository);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::factory()->create();
    }

    public function test_ancla_el_asunto_al_de_la_primera_fila_real_no_al_de_ticket_subject(): void
    {
        $ticket = $this->makeTicket(['subject' => 'Contacto general']);
        $this->makeMail($ticket, 'outbound', 'Hemos recibido tu solicitud — #'.$ticket->ticket_number);
        $this->makeMail($ticket, 'inbound', 'Re: Hemos recibido tu solicitud — #'.$ticket->ticket_number);

        $this->assertSame(
            'Re: Hemos recibido tu solicitud — #'.$ticket->ticket_number,
            $this->service->threadSubject($ticket)
        );
    }

    public function test_no_duplica_re_ni_el_numero_de_ticket_al_anclar(): void
    {
        $ticket = $this->makeTicket(['subject' => 'Contacto general']);
        $this->makeMail($ticket, 'outbound', 'Hemos recibido tu solicitud — #'.$ticket->ticket_number);

        $subject = $this->service->threadSubject($ticket);

        $this->assertSame(1, substr_count($subject, 'Re:'));
        $this->assertSame(1, substr_count($subject, $ticket->ticket_number));
    }

    public function test_usa_ticket_subject_si_no_hay_ningun_correo_todavia(): void
    {
        $ticket = $this->makeTicket(['subject' => 'Contacto general']);

        $this->assertSame(
            'Re: Contacto general — #'.$ticket->ticket_number,
            $this->service->threadSubject($ticket)
        );
    }

    public function test_usa_la_primera_fila_no_la_ultima(): void
    {
        // Si una fila intermedia quedó "rota" (asunto ya divergente por el
        // propio bug), anclar siempre a la PRIMERA la corrige de forma
        // autocurativa en vez de arrastrar el problema hacia delante.
        $ticket = $this->makeTicket(['subject' => 'Contacto general']);
        $primero = $this->makeMail($ticket, 'outbound', 'Hemos recibido tu solicitud — #'.$ticket->ticket_number);
        $primero->forceFill(['created_at' => now()->subHour()])->save();
        $this->makeMail($ticket, 'outbound', 'Re: Contacto general — #'.$ticket->ticket_number);

        $this->assertSame(
            'Re: Hemos recibido tu solicitud — #'.$ticket->ticket_number,
            $this->service->threadSubject($ticket)
        );
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Test',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'email',
        ], $overrides));
    }

    private function makeMail(Ticket $ticket, string $direction, string $subject): TicketMail
    {
        return TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => $direction,
            'message_id' => '<'.uniqid().'@example.com>',
            'from' => 'cliente@example.com',
            'to' => 'info@example.com',
            'subject' => $subject,
            'status' => $direction === 'inbound' ? 'received' : 'sent',
        ]);
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
