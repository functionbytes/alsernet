<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Jobs\Helpdesks\FetchTicketEmailsJob;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketService;
use Tests\TestCase;
use Webklex\PHPIMAP\Attribute as ImapAttribute;
use Webklex\PHPIMAP\Message as ImapMessage;
use Webklex\PHPIMAP\Support\AttachmentCollection;

// ---------------------------------------------------------------------------
// Helper subclass to expose protected methods and stub out processConnection
// ---------------------------------------------------------------------------

class TestableFetchTicketEmailsJob extends FetchTicketEmailsJob
{
    public function injectTicketService(TicketService $service): void
    {
        $this->ticketService = $service;
    }

    public function callExtractEmailAddress(string $from): string
    {
        return $this->extractEmailAddress($from);
    }

    public function callExtractEmailName(string $from): ?string
    {
        return $this->extractEmailName($from);
    }

    public function callDetectPriority(string $subject): string
    {
        return $this->detectPriority($subject);
    }

    public function callFindOrCreateTicket(array $parsed, array $connection): ?Ticket
    {
        return $this->findOrCreateTicket($parsed, $connection);
    }

    public function callProcessIncomingEmail(object $message, array $connection): void
    {
        $this->processIncomingEmail($message, $connection);
    }

    public function callStringAttribute(?ImapAttribute $attribute): ?string
    {
        return $this->stringAttribute($attribute);
    }

    public function callFormatAddressAttribute(?ImapAttribute $attribute): ?string
    {
        return $this->formatAddressAttribute($attribute);
    }

    public function callDecodeMimeHeader(string $value): string
    {
        return $this->decodeMimeHeader($value);
    }

    /**
     * @return array<int, string>
     */
    public function callSplitReferences(?string $references): array
    {
        return $this->splitReferences($references);
    }

    protected function processConnection(array $connection): void
    {
        // no-op — prevents real IMAP connections in tests
    }
}

/**
 * Doble de prueba de Webklex\PHPIMAP\Message: el real exige una conexión
 * IMAP viva en su constructor (Client, folder, fetch de verdad), así que no
 * se puede instanciar ni mockear de forma barata en un test unitario. Se
 * sobreescribe el constructor a un no-op y solo los métodos que
 * FetchTicketEmailsJob realmente llama; message_id/from/to/subject/etc. se
 * asignan por la propiedad mágica heredada (Message::__set() los guarda en
 * $attributes, y Message::__get()/get() los devuelve tal cual sin tocar IMAP).
 */
class FakeImapMessage extends ImapMessage
{
    private string $fakeTextBody = '';

    private string $fakeHtmlBody = '';

    private string $fakeRawBody = '';

    public int $seenFlagCalls = 0;

    public bool $failSeenFlag = false;

    public function __construct()
    {
        // No parent::__construct(): requiere un Client IMAP real.
    }

    public function withTextBody(string $body): static
    {
        $this->fakeTextBody = $body;

        return $this;
    }

    public function withHtmlBody(string $body): static
    {
        $this->fakeHtmlBody = $body;

        return $this;
    }

    public function getTextBody(): string
    {
        return $this->fakeTextBody;
    }

    public function getHTMLBody(): string
    {
        return $this->fakeHtmlBody;
    }

    public function getRawBody(): string
    {
        return $this->fakeRawBody;
    }

    public function getAttachments(): AttachmentCollection
    {
        return new AttachmentCollection;
    }

    /**
     * Simula el fallo real que dispara este bug: setFlag('Seen') que lanza
     * en vez de confirmar, dejando el mensaje como no leído para la próxima
     * corrida (ver processMessage() en el job).
     */
    public function setFlag(array|string $flag): bool
    {
        $this->seenFlagCalls++;

        if ($this->failSeenFlag) {
            throw new \RuntimeException('IMAP setFlag failed (simulated)');
        }

        return true;
    }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

class FetchTicketEmailsJobTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' es la conexión real de Setting/settings (confirmado en runtime,
    // no un alias de 'mariadb') — sin ella aquí, seedIncomingEmailSetting()
    // escribe DE VERDAD y sin rollback: en este entorno compartido llegó a
    // borrar un canal de correo real ya configurado por otra vía. No es solo
    // caché (ver reference_setting_cache_escapes_db_transaction.md): es la
    // fila en BD, fuera de cualquier transacción, nunca revertida.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTicketService(): TicketService
    {
        return new TicketService;
    }

    private function makeJob(): TestableFetchTicketEmailsJob
    {
        $job = new TestableFetchTicketEmailsJob;
        $job->injectTicketService($this->makeTicketService());

        return $job;
    }

    private function seedIncomingEmailSetting(array $connections): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'incoming_email'],
            ['value' => json_encode(['imap' => ['connections' => $connections]])]
        );

        Cache::forget('setting_incoming_email');
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

    /**
     * Construye un FakeImapMessage con los atributos que
     * processIncomingEmail() de verdad lee. Los campos opcionales (in_reply_to,
     * references, cc, bcc, date) se dejan como Attribute VACÍOS —no null—:
     * Message::get() sin un valor en $attributes cae a $this->header->get(),
     * y aquí $header nunca se inicializa (el doble no pasa por el
     * constructor real), así que un null literal dispararía un error de
     * "member function get() on null" en vez de simular limpiamente "esta
     * cabecera no vino en el correo".
     */
    private function makeIncomingImapMessage(?string $messageId, string $fromEmail, string $subject, string $body): FakeImapMessage
    {
        [$mailbox, $host] = explode('@', $fromEmail, 2);

        $message = new FakeImapMessage;
        $message->message_id = new ImapAttribute('message_id', $messageId ?? '');
        $message->in_reply_to = new ImapAttribute('in_reply_to');
        $message->references = new ImapAttribute('references');
        $message->from = new ImapAttribute('from', (object) ['personal' => '', 'mailbox' => $mailbox, 'host' => $host]);
        $message->to = new ImapAttribute('to', (object) ['personal' => '', 'mailbox' => 'support', 'host' => 'example.com']);
        $message->cc = new ImapAttribute('cc');
        $message->bcc = new ImapAttribute('bcc');
        $message->subject = new ImapAttribute('subject', $subject);
        $message->date = new ImapAttribute('date');

        return $message->withTextBody($body);
    }

    // -------------------------------------------------------------------------
    // processIncomingEmail() — idempotencia por Message-ID (BUG-02)
    // -------------------------------------------------------------------------

    /**
     * Reproduce el bug real: $message->setFlag('Seen') falla en silencio (o,
     * como aquí, se simula procesando dos veces el MISMO mensaje IMAP), así
     * que el correo sigue apareciendo como no leído y FetchTicketEmailsJob lo
     * vuelve a traer en la siguiente corrida con idéntico Message-ID. Sin el
     * guard de idempotencia, la segunda pasada crearía un segundo ticket y
     * dispararía TicketCreated (y su SendCustomerConfirmation) otra vez.
     */
    public function test_processing_the_same_message_id_twice_creates_only_one_ticket(): void
    {
        Event::fake();

        $messageId = 'dup-msg-'.uniqid().'@example.com';
        $connection = $this->baseConnection(['create_tickets' => true, 'create_replies' => true]);
        $job = $this->makeJob();

        $message = $this->makeIncomingImapMessage($messageId, 'idempotent@example.com', 'Necesito ayuda', 'Primer intento.');
        $job->callProcessIncomingEmail($message, $connection);

        // Mismo Message-ID, "segunda corrida" del mismo correo no leído.
        $message = $this->makeIncomingImapMessage($messageId, 'idempotent@example.com', 'Necesito ayuda', 'Primer intento.');
        $job->callProcessIncomingEmail($message, $connection);

        $this->assertSame(1, TicketMail::where('message_id', $messageId)->count());
        $this->assertSame(1, Ticket::where('subject', 'Necesito ayuda')->count());
        Event::assertDispatched(TicketCreated::class, 1);
    }

    /**
     * Algunos servidores entregan mensajes sin Message-ID. El UID IMAP sí
     * permanece estable mientras el mensaje siga en la misma carpeta, por lo
     * que tampoco se debe duplicar si el flag Seen falla y vuelve a entrar en
     * la siguiente corrida.
     */
    public function test_processing_the_same_message_without_message_id_uses_imap_uid_for_idempotency(): void
    {
        Event::fake();

        $connection = $this->baseConnection(['create_tickets' => true, 'create_replies' => true]);
        $job = $this->makeJob();

        $message = $this->makeIncomingImapMessage(null, 'uid@example.com', 'Correo sin identificador', 'Mismo correo.');
        $message->uid = 7742;
        $job->callProcessIncomingEmail($message, $connection);

        $message = $this->makeIncomingImapMessage(null, 'uid@example.com', 'Correo sin identificador', 'Mismo correo.');
        $message->uid = 7742;
        $job->callProcessIncomingEmail($message, $connection);

        $this->assertSame(1, TicketMail::where('direction', 'inbound')->where('subject', 'Correo sin identificador')->count());
        $this->assertSame(1, Ticket::where('subject', 'Correo sin identificador')->count());
        Event::assertDispatched(TicketCreated::class, 1);
    }

    // -------------------------------------------------------------------------
    // handle() — filtering logic
    // -------------------------------------------------------------------------

    private function makeCountingJob(): object
    {
        return new class extends TestableFetchTicketEmailsJob
        {
            public int $count = 0;

            protected function processConnection(array $connection): void
            {
                $this->count++;
            }
        };
    }

    public function test_handle_skips_connections_with_no_create_flags(): void
    {
        $this->seedIncomingEmailSetting([
            $this->baseConnection(['create_tickets' => false, 'create_replies' => false]),
        ]);

        $job = $this->makeCountingJob();
        $job->handle($this->makeTicketService());

        $this->assertSame(0, $job->count);
    }

    public function test_handle_processes_connections_with_create_tickets(): void
    {
        $this->seedIncomingEmailSetting([
            $this->baseConnection(['create_tickets' => true, 'create_replies' => false]),
        ]);

        $job = $this->makeCountingJob();
        $job->handle($this->makeTicketService());

        $this->assertSame(1, $job->count);
    }

    public function test_handle_processes_connections_with_create_replies(): void
    {
        $this->seedIncomingEmailSetting([
            $this->baseConnection(['create_tickets' => false, 'create_replies' => true]),
        ]);

        $job = $this->makeCountingJob();
        $job->handle($this->makeTicketService());

        $this->assertSame(1, $job->count);
    }

    // -------------------------------------------------------------------------
    // extractEmailAddress — pure logic, no DB needed
    // -------------------------------------------------------------------------

    public function test_extract_email_address_from_angle_bracket_format(): void
    {
        $result = $this->makeJob()->callExtractEmailAddress('John Doe <john@example.com>');

        $this->assertSame('john@example.com', $result);
    }

    public function test_extract_email_address_plain(): void
    {
        $result = $this->makeJob()->callExtractEmailAddress('john@example.com');

        $this->assertSame('john@example.com', $result);
    }

    // -------------------------------------------------------------------------
    // extractEmailName — pure logic, no DB needed
    // -------------------------------------------------------------------------

    public function test_extract_email_name_from_display_name(): void
    {
        $result = $this->makeJob()->callExtractEmailName('John Doe <john@example.com>');

        $this->assertSame('John Doe', $result);
    }

    public function test_extract_email_name_returns_null_for_plain_email(): void
    {
        $result = $this->makeJob()->callExtractEmailName('john@example.com');

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // detectPriority — pure logic, no DB needed
    // -------------------------------------------------------------------------

    public function test_detect_priority_urgent_keyword(): void
    {
        $this->assertSame('urgent', $this->makeJob()->callDetectPriority('URGENT: Help needed'));
    }

    public function test_detect_priority_low_keyword(): void
    {
        $this->assertSame('low', $this->makeJob()->callDetectPriority('low priority request'));
    }

    public function test_detect_priority_defaults_to_normal(): void
    {
        $this->assertSame('normal', $this->makeJob()->callDetectPriority('Hello there'));
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

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

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

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

        $this->assertNotNull($result);
        $this->assertSame($ticket->id, $result->id);
    }

    public function test_find_or_create_ticket_threads_via_references_chain(): void
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

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

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

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

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
        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

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

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

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

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

        $this->assertInstanceOf(Ticket::class, $result);
        $this->assertDatabaseHas('helpdesk_tickets', ['subject' => 'Brand new issue'], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customers', ['email' => 'newcustomer@example.com'], 'helpdesk');

        // Regresión: un ticket nacido de un correo real nunca disparaba
        // TicketCreated (a diferencia de TicketService::createTicket(), usado
        // por widget/formulario público) — SendCustomerConfirmation,
        // NotifyAgentsOnNewTicket, etc. nunca corrían para el único canal de
        // entrada real de tickets. Ver FetchTicketEmailsJob::findOrCreateTicket().
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

        $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

        $customer = Customer::where('email', 'erp.newcustomer@example.com')->first();
        $this->assertNotNull($customer);

        Queue::assertPushed(LinkCustomerToErpJob::class, function (LinkCustomerToErpJob $job) use ($customer) {
            return $job->uniqueId() === (string) $customer->id;
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

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => false]));

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

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

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

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

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

        $result = $this->makeJob()->callFindOrCreateTicket($parsed, $this->baseConnection(['create_tickets' => true]));

        $this->assertInstanceOf(Ticket::class, $result);
        $this->assertDatabaseHas('helpdesk_tickets', ['subject' => 'Still gets through'], 'helpdesk');
    }

    // -------------------------------------------------------------------------
    // webklex/php-imap adapter helpers — migración desde PhpImap\Mailbox
    // (barbushin/php-imap), que nunca estuvo instalado (composer.json solo
    // trae webklex/php-imap): cualquier canal real con create_tickets/
    // create_replies activo hacía fallar el job entero con "Class not found"
    // en cuanto intentaba conectar de verdad.
    // -------------------------------------------------------------------------

    public function test_decode_mime_header_decodes_encoded_word(): void
    {
        $result = $this->makeJob()->callDecodeMimeHeader('=?utf-8?b?V2hhdOKAmXM=?= new for developers at Oracle');

        $this->assertSame("What\u{2019}s new for developers at Oracle", $result);
    }

    public function test_decode_mime_header_leaves_plain_text_untouched(): void
    {
        $result = $this->makeJob()->callDecodeMimeHeader('Plain subject, no encoding');

        $this->assertSame('Plain subject, no encoding', $result);
    }

    public function test_string_attribute_returns_null_for_null_attribute(): void
    {
        $this->assertNull($this->makeJob()->callStringAttribute(null));
    }

    public function test_string_attribute_returns_plain_value(): void
    {
        $attribute = new ImapAttribute('message_id', 'abc123@example.com');

        $this->assertSame('abc123@example.com', $this->makeJob()->callStringAttribute($attribute));
    }

    public function test_format_address_attribute_returns_null_for_null_attribute(): void
    {
        $this->assertNull($this->makeJob()->callFormatAddressAttribute(null));
    }

    public function test_format_address_attribute_formats_name_and_email(): void
    {
        $attribute = new ImapAttribute('from', (object) [
            'personal' => 'John Doe',
            'mailbox' => 'john',
            'host' => 'example.com',
        ]);

        $result = $this->makeJob()->callFormatAddressAttribute($attribute);

        $this->assertSame('John Doe <john@example.com>', $result);
    }

    public function test_format_address_attribute_omits_brackets_without_a_display_name(): void
    {
        $attribute = new ImapAttribute('from', (object) [
            'personal' => '',
            'mailbox' => 'john',
            'host' => 'example.com',
        ]);

        $result = $this->makeJob()->callFormatAddressAttribute($attribute);

        $this->assertSame('john@example.com', $result);
    }

    public function test_format_address_attribute_joins_multiple_recipients(): void
    {
        $attribute = new ImapAttribute('to', [
            (object) ['personal' => '', 'mailbox' => 'a', 'host' => 'example.com'],
            (object) ['personal' => 'B Person', 'mailbox' => 'b', 'host' => 'example.com'],
        ]);

        $result = $this->makeJob()->callFormatAddressAttribute($attribute);

        $this->assertSame('a@example.com, B Person <b@example.com>', $result);
    }

    public function test_split_references_returns_empty_array_for_null(): void
    {
        $this->assertSame([], $this->makeJob()->callSplitReferences(null));
    }

    public function test_split_references_trims_brackets_and_whitespace(): void
    {
        $result = $this->makeJob()->callSplitReferences('<a@example.com>, b@example.com , <c@example.com>');

        $this->assertSame(['a@example.com', 'b@example.com', 'c@example.com'], $result);
    }

    public function test_format_address_attribute_decodes_mime_encoded_display_name(): void
    {
        $attribute = new ImapAttribute('from', (object) [
            'personal' => '=?utf-8?b?V2hhdOKAmXM=?=',
            'mailbox' => 'john',
            'host' => 'example.com',
        ]);

        $result = $this->makeJob()->callFormatAddressAttribute($attribute);

        $this->assertSame("What\u{2019}s <john@example.com>", $result);
    }
}
