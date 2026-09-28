<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Support;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Support\InboundEmailTicketResolver;
use Tests\TestCase;

/**
 * Movido de FetchTicketEmailsJobTest (30-sep-2026, refactor estructural que
 * extrajo el hilado/creación de tickets de FetchTicketEmailsJob a
 * InboundEmailTicketResolver) a apuntar directamente a esa clase:
 * findOrCreateTicket() es público, ya no hace falta pasar por el job ni por
 * la subclase de prueba que exponía el método protegido.
 */
class InboundEmailTicketResolverTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' es la conexión real de Setting/settings (confirmado en runtime,
    // no un alias de 'mariadb') — ver feedback_settings_tests_corrupted_real_channel_data.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        TicketStatus::firstOrCreate(
            ['slug' => 'new'],
            [
                'name' => 'Nuevo',
                'color' => '#22c55e',
                'is_open' => true,
                'is_default' => true,
                'is_system' => true,
                'stops_sla_timer' => false,
                'active' => true,
            ]
        );
    }

    private function resolver(): InboundEmailTicketResolver
    {
        return app(InboundEmailTicketResolver::class);
    }

    private function baseConnection(array $overrides = []): array
    {
        return array_merge([
            'name' => 'test-connection',
            'host' => 'imap.example.com',
            'port' => 993,
            'username' => 'user@example.com',
            'password' => 'secret',
            'encryption' => 'ssl',
            'folder' => 'INBOX',
            'create_tickets' => false,
            'create_replies' => false,
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // findOrCreateTicket
    // -------------------------------------------------------------------------

    public function test_find_or_create_ticket_threads_via_in_reply_to(): void
    {
        // El email del cliente tiene que ser el mismo desde el que llega la
        // respuesta: findOrCreateTicket() ya solo hila cuando el remitente es
        // el cliente del ticket, también por Message-ID. Con la factory sin
        // argumentos salía un email aleatorio y la fixture describía en
        // realidad a un tercero respondiendo a un hilo reenviado — el caso
        // que ahora se rechaza a propósito (ver el test de más abajo).
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);

        $status = TicketStatus::where('slug', 'new')->first();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
        ]);

        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => '<original-msg-id-'.uniqid().'@example.com>',
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'subject' => 'Help needed',
            'body_text' => 'Please help',
            'status' => 'received',
            'attachments' => [],
            'headers' => [],
        ]);

        $parsed = [
            'message_id' => '<reply-msg-id-'.uniqid().'@example.com>',
            'in_reply_to' => $mail->message_id,
            'references' => null,
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Re: Help needed',
            'body_text' => 'More info',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

        $this->assertNotNull($result);
        $this->assertSame($ticket->id, $result->id);
    }

    /**
     * Regresión: message_id de nuestras propias respuestas (TicketMailDispatcher,
     * SendCustomerReplyNotification, TicketCommentsController, TicketMail::
     * createOutbound()) se guardaba con '<' '>', pero webklex/php-imap normaliza
     * el In-Reply-To entrante SIN corchetes — la comparación exacta de string
     * nunca enganchaba y la respuesta del cliente abría un ticket nuevo en vez
     * de continuar el mismo. Ahora ambos se guardan sin corchetes.
     */
    public function test_find_or_create_ticket_threads_when_replying_to_our_own_outbound_message(): void
    {
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $status = TicketStatus::where('slug', 'new')->first();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
        ]);

        // Mismo formato (sin corchetes) que TicketMailDispatcher::send()/
        // SendCustomerReplyNotification tras el fix.
        $ourOutboundMessageId = Str::uuid().'@webadmin.test';

        TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'message_id' => $ourOutboundMessageId,
            'from' => 'support@example.com',
            'to' => 'customer@example.com',
            'subject' => 'Re: Help needed',
            'body_text' => 'Aquí tienes la respuesta.',
            'status' => 'sent',
        ]);

        $parsed = [
            'message_id' => '<reply-msg-id-'.uniqid().'@example.com>',
            // Tal como llega normalizado por webklex/php-imap: sin corchetes.
            'in_reply_to' => $ourOutboundMessageId,
            'references' => null,
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Re: Re: Help needed',
            'body_text' => 'Gracias por la respuesta.',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

        $this->assertNotNull($result);
        $this->assertSame($ticket->id, $result->id);
    }

    public function test_find_or_create_ticket_threads_via_references_chain(): void
    {
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $status = TicketStatus::where('slug', 'new')->first();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
        ]);

        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => 'original-msg-id-'.uniqid().'@example.com',
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'subject' => 'Help needed',
            'body_text' => 'Please help',
            'status' => 'received',
        ]);

        $parsed = [
            'message_id' => 'reply-msg-id-'.uniqid().'@example.com',
            // Sin In-Reply-To directo (algunos clientes solo llenan References,
            // o se responde a un mensaje intermedio del hilo) — el match debe
            // resolverse por la cadena de References, no solo por el padre
            // inmediato.
            'in_reply_to' => null,
            'references' => 'algun-otro-id@example.com, '.$mail->message_id,
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Re: Help needed',
            'body_text' => 'More info',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

        $this->assertNotNull($result);
        $this->assertSame($ticket->id, $result->id);
    }

    public function test_find_or_create_ticket_threads_via_space_separated_references_chain(): void
    {
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $status = TicketStatus::where('slug', 'new')->first();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
        ]);

        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => 'first@example.com',
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'subject' => 'Help needed',
            'body_text' => 'Please help',
            'status' => 'received',
            'attachments' => [],
            'headers' => [],
        ]);

        $parsed = [
            'message_id' => 'reply@example.com',
            'in_reply_to' => null,
            'references' => '<unrelated@example.com> <'.$mail->message_id.'>',
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Re: Help needed',
            'body_text' => 'More info',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

        $this->assertNotNull($result);
        $this->assertSame($ticket->id, $result->id);
    }

    /**
     * El hilado por asunto (#TCK-…) ya verificaba que el remitente fuese el
     * cliente del ticket, con el motivo escrito en el propio código: "otherwise
     * a third party could inject messages into someone else's ticket". La rama
     * de Message-ID, que va antes, no lo hacía.
     *
     * Los Message-ID salientes no son adivinables, pero sí circulan: basta con
     * que el cliente reenvíe el correo del helpdesk a un tercero para que ese
     * tercero tenga la cabecera en su copia y, respondiendo, escriba dentro de
     * un ticket que no es suyo.
     */
    public function test_find_or_create_ticket_does_not_thread_when_message_id_sender_is_a_third_party(): void
    {
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);

        $status = TicketStatus::where('slug', 'new')->first();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
        ]);

        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => 'original-msg-id-'.uniqid().'@example.com',
            'from' => 'customer@example.com',
            'to' => 'support@example.com',
            'subject' => 'Help needed',
            'body_text' => 'Please help',
            'status' => 'received',
        ]);

        $parsed = [
            'message_id' => 'reply-msg-id-'.uniqid().'@example.com',
            'in_reply_to' => $mail->message_id,
            'references' => null,
            // Alguien a quien el cliente reenvió el hilo, no el cliente.
            'from' => 'un-tercero@otra-empresa.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Re: Help needed',
            'body_text' => 'Me han reenviado esto',
            'body_html' => null,
            'attachments' => [],
        ];

        // create_tickets = false para aislar la comprobación: si no se hila,
        // esta conexión no abre ticket nuevo y el resultado es null.
        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

        $this->assertNull($result, 'Un tercero no debe poder escribir en el ticket de otro por Message-ID.');
    }

    /**
     * La lista negra se evaluaba DESPUÉS del hilado por Message-ID, así que un
     * remitente bloqueado que respondiera a un hilo existente se colaba entero.
     */
    public function test_blacklisted_sender_is_discarded_even_when_replying_to_an_existing_thread(): void
    {
        $customer = Customer::factory()->create(['email' => 'spammer@example.com']);

        $status = TicketStatus::where('slug', 'new')->first();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
        ]);

        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => 'original-msg-id-'.uniqid().'@example.com',
            'from' => 'spammer@example.com',
            'to' => 'support@example.com',
            'subject' => 'Help needed',
            'body_text' => 'Please help',
            'status' => 'received',
        ]);

        TicketEmailBlacklist::create([
            'type' => 'email',
            'value' => 'spammer@example.com',
            'reason' => 'Prueba',
            'is_active' => true,
        ]);

        $parsed = [
            'message_id' => 'reply-msg-id-'.uniqid().'@example.com',
            'in_reply_to' => $mail->message_id,
            'references' => null,
            'from' => 'spammer@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Re: Help needed',
            'body_text' => 'Mas spam',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

        $this->assertNull($result, 'La lista negra debe aplicarse antes de cualquier hilado.');
    }

    public function test_find_or_create_ticket_creates_new_ticket_when_no_thread(): void
    {
        Event::fake();

        $parsed = [
            'message_id' => '<new-msg-id@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'from' => 'newcustomer@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Brand new issue',
            'body_text' => 'I need help.',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

        $this->assertInstanceOf(Ticket::class, $result);
        $this->assertDatabaseHas('helpdesk_tickets', ['subject' => 'Brand new issue'], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customers', ['email' => 'newcustomer@example.com'], 'helpdesk');

        // Regresión: un ticket nacido de un correo real nunca disparaba
        // TicketCreated (a diferencia de TicketService::createTicket(), usado
        // por widget/formulario público) — SendCustomerConfirmation,
        // NotifyAgentsOnNewTicket, etc. nunca corrían para el único canal de
        // entrada real de tickets. Ver InboundEmailTicketResolver::findOrCreateTicket().
        Event::assertDispatched(TicketCreated::class, fn ($event) => $event->ticket->is($result));
    }

    /**
     * Mismo mecanismo que ConversationCreated -> DispatchErpLinkJob en el
     * núcleo Helpdesk: al crear el Customer se despacha el vínculo con el
     * ERP en segundo plano (best-effort, nunca bloquea la creación del
     * ticket). Solo se comprueba el dispatch aquí; linkCustomer() en sí ya
     * está cubierto en los tests del módulo HelpdeskErp.
     */
    public function test_find_or_create_ticket_dispatches_erp_link_job_for_new_customer(): void
    {
        if (! helpdesk_erp_enabled()) {
            $this->markTestSkipped('Módulo HelpdeskErp no activo en este entorno.');
        }

        Queue::fake();

        $parsed = [
            'message_id' => '<erp-link-msg@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'from' => 'erp.newcustomer@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Necesito ayuda con mi pedido',
            'body_text' => 'Hola.',
            'body_html' => null,
            'attachments' => [],
        ];

        $ticket = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

        $customer = Customer::where('email', 'erp.newcustomer@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertNotNull($ticket);

        // uniqueId() incluye el origen (cliente:ticket:id:) desde que cada
        // ticket tiene su propio trabajo — ver LinkCustomerToErpJob::uniqueId().
        Queue::assertPushed(LinkCustomerToErpJob::class, function (LinkCustomerToErpJob $job) use ($customer, $ticket) {
            return $job->uniqueId() === "{$customer->id}:ticket:{$ticket->id}:";
        });
    }

    public function test_find_or_create_ticket_returns_null_when_create_tickets_disabled(): void
    {
        $parsed = [
            'message_id' => '<no-thread-msg@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'from' => 'stranger@example.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Random email',
            'body_text' => 'Not a reply.',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

        $this->assertNull($result);
        $this->assertDatabaseMissing('helpdesk_tickets', ['subject' => 'Random email'], 'helpdesk');
    }

    // -------------------------------------------------------------------------
    // findOrCreateTicket — sender blacklist
    // -------------------------------------------------------------------------

    public function test_find_or_create_ticket_discards_email_when_sender_blacklisted(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'email', 'value' => 'blocked@spam.com']);

        $parsed = [
            'message_id' => '<blacklisted-msg@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'from' => 'blocked@spam.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Buy now',
            'body_text' => 'Spam.',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

        $this->assertNull($result);
        $this->assertDatabaseMissing('helpdesk_tickets', ['subject' => 'Buy now'], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_customers', ['email' => 'blocked@spam.com'], 'helpdesk');

        $rule->refresh();
        $this->assertSame(1, $rule->matched_count);
        $this->assertNotNull($rule->last_matched_at);
    }

    public function test_find_or_create_ticket_discards_email_when_sender_domain_blacklisted(): void
    {
        TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);

        $parsed = [
            'message_id' => '<blacklisted-domain-msg@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'from' => 'someone@mail.spam.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Subdomain spam',
            'body_text' => 'Spam.',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

        $this->assertNull($result);
        $this->assertDatabaseMissing('helpdesk_customers', ['email' => 'someone@mail.spam.com'], 'helpdesk');
    }

    public function test_find_or_create_ticket_ignores_inactive_blacklist_rule(): void
    {
        Event::fake();

        TicketEmailBlacklist::create([
            'type' => 'email',
            'value' => 'notblocked@spam.com',
            'is_active' => false,
        ]);

        $parsed = [
            'message_id' => '<inactive-rule-msg@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'from' => 'notblocked@spam.com',
            'to' => 'support@example.com',
            'cc' => null,
            'bcc' => null,
            'subject' => 'Still gets through',
            'body_text' => 'Not blocked, rule is inactive.',
            'body_html' => null,
            'attachments' => [],
        ];

        $result = $this->resolver()->findOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

        $this->assertInstanceOf(Ticket::class, $result);
        $this->assertDatabaseHas('helpdesk_tickets', ['subject' => 'Still gets through'], 'helpdesk');
    }
}
