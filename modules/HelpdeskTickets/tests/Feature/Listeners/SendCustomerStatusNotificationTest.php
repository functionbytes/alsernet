<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Listeners;

use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Listeners\SendCustomerStatusNotification;
use Modules\HelpdeskTickets\Mail\TicketReplyMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;
use Tests\TestCase;

/**
 * Gap de cobertura de alto riesgo (auditoría 14-sep-2026): este listener
 * envía un correo REAL al cliente en cada cambio de estado del ticket, sin
 * ningún test que fijara cuándo debe (y sobre todo cuándo NO debe) disparar
 * ese envío.
 */
class SendCustomerStatusNotificationTest extends TestCase
{
    // mariadb y helpdesk apuntan a la misma BD física en test: mismo patrón
    // que SendCustomerReplyNotificationTest para evitar auto-interbloqueos de
    // FK entre ambas conexiones (ver docblock del trait). Trae
    // DatabaseTransactions consigo, no declarar aparte.
    use SharesHelpdeskPdo;

    private TicketStatus $openStatus;

    private TicketStatus $closedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->closedStatus = TicketStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#6c757d', 'is_open' => false, 'is_default' => false, 'order' => 2]
        );
    }

    private function createEnabledTemplate(): MailerTemplate
    {
        $langId = MailerLang::resolveDefaultId();

        // updateOrCreate, no create(): con 'mysql' en connectionsToTransact
        // (heredado de SharesHelpdeskPdo) la plantilla REAL sembrada con esta
        // misma key es visible dentro de la transacción del test — un
        // create() a ciegas chocaría con su unique key en vez de fallar en
        // silencio.
        $template = MailerTemplate::updateOrCreate(
            ['key' => 'helpdesk.ticket_status_changed'],
            ['name' => 'Ticket status changed', 'module' => 'helpdesktickets', 'is_enabled' => true]
        );

        MailerTemplateLang::updateOrCreate(
            ['mailer_template_id' => $template->id, 'lang_id' => $langId],
            [
                'subject' => 'Tu ticket #{TICKET_NUMBER} cambió a: {NEW_STATUS}',
                'content' => '<p>Hola {CUSTOMER_NAME}, tu ticket {TICKET_NUMBER} pasó de {OLD_STATUS} a {NEW_STATUS}.</p>',
            ]
        );

        return $template;
    }

    private function createTicket(?string $email = 'status-test-customer@example.com'): Ticket
    {
        $customer = $email
            ? Customer::firstOrCreate(['email' => $email], ['name' => 'Status Test Customer'])
            : null;

        return Ticket::create([
            'subject' => 'Test ticket',
            'description' => 'Test description.',
            'customer_id' => $customer?->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);
    }

    public function test_status_change_sends_email_to_the_correct_customer_with_ticket_number(): void
    {
        Mail::fake();
        $this->createEnabledTemplate();

        $ticket = $this->createTicket('juan.perez@example.com');

        $listener = app(SendCustomerStatusNotification::class);
        $listener->handle(new TicketStatusChanged($ticket, $this->openStatus, $this->closedStatus));

        // TicketReplyMail implements ShouldQueue: Mailer::send() lo detecta y
        // lo encola en vez de enviarlo en el mismo request — MailFake lo
        // registra como "queued", no como "sent".
        Mail::assertQueued(TicketReplyMail::class, function (TicketReplyMail $mail) use ($ticket) {
            return $mail->hasTo('juan.perez@example.com')
                && str_contains($mail->emailSubject, (string) $ticket->ticket_number)
                && str_contains($mail->emailContent, (string) $ticket->ticket_number);
        });
    }

    public function test_no_email_is_sent_when_the_status_does_not_actually_change(): void
    {
        Mail::fake();
        $this->createEnabledTemplate();

        $ticket = $this->createTicket();

        $listener = app(SendCustomerStatusNotification::class);
        // newStatus y previousStatus son el MISMO estado (mismo id): el
        // listener debe cortar antes de tocar plantilla o mailer.
        $listener->handle(new TicketStatusChanged($ticket, $this->openStatus, $this->openStatus));

        Mail::assertNothingSent();
    }

    public function test_no_email_is_sent_when_the_ticket_has_no_customer_email(): void
    {
        Mail::fake();
        $this->createEnabledTemplate();

        $ticket = $this->createTicket(null);

        $listener = app(SendCustomerStatusNotification::class);
        $listener->handle(new TicketStatusChanged($ticket, $this->openStatus, $this->closedStatus));

        Mail::assertNothingSent();
    }

    public function test_no_email_is_sent_when_the_template_is_disabled(): void
    {
        Mail::fake();
        $template = $this->createEnabledTemplate();
        $template->update(['is_enabled' => false]);

        $ticket = $this->createTicket();

        $listener = app(SendCustomerStatusNotification::class);
        $listener->handle(new TicketStatusChanged($ticket, $this->openStatus, $this->closedStatus));

        Mail::assertNothingSent();
    }
}
