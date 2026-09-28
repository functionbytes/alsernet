<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Jobs\Helpdesks\FetchTicketEmailsJob;
use Modules\HelpdeskTickets\Models\Ticket;
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

/**
 * Trocado (30-sep-2026, ver docblock de FetchTicketEmailsJob) en delegados
 * más finos bajo app/Support/: extractEmailAddress/extractEmailName/
 * detectPriority/findOrCreateTicket/stringAttribute/formatAddressAttribute/
 * decodeMimeHeader/splitReferences y allowedAttachmentExtensions se prueban
 * ahora directamente contra InboundEmailMessageParser/InboundEmailTicketResolver/
 * InboundEmailAttachmentStorer (ver tests/Unit/Support/) — este archivo se
 * queda solo con lo que ejercita de verdad al JOB: processIncomingEmail()
 * (orquestación completa, idempotencia) y handle() (filtrado de conexiones).
 */
class TestableFetchTicketEmailsJob extends FetchTicketEmailsJob
{
    public function injectTicketService(TicketService $service): void
    {
        $this->ticketService = $service;
    }

    public function callProcessIncomingEmail(object $message, array $connection): void
    {
        $this->processIncomingEmail($message, $connection);
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
}
