<?php

namespace Modules\HelpdeskTickets\Jobs\Helpdesks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Setting;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\SpamClassifierService;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Modules\HelpdeskTickets\Services\TicketService;
use Webklex\PHPIMAP\Attachment as ImapAttachment;
use Webklex\PHPIMAP\Attribute as ImapAttribute;
use Webklex\PHPIMAP\Client as ImapClient;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message as ImapMessage;

class FetchTicketEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    protected ?TicketService $ticketService = null;

    /**
     * Cuando se define, solo se procesa el canal con este id (usado por el
     * botón "Sincronizar ahora" de Settings → Canales de correo); null
     * procesa todos los canales, igual que la corrida agendada.
     */
    public function __construct(protected ?string $onlyConnectionId = null)
    {
        $this->queue = 'helpdesk-scheduled';
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('fetch-ticket-emails'))->dontRelease()];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FetchTicketEmailsJob permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Execute the job. Procesa las conexiones IMAP definidas en el setting
     * `incoming_email` (multi-buzón); si no hay ninguna, cae al buzón único de
     * config('helpdesk.email.imap') para no romper instalaciones existentes.
     */
    public function handle(?TicketService $ticketService = null): void
    {
        $this->ticketService = $ticketService;

        try {
            $connections = $this->incomingConnections();

            // El fallback al buzón único legacy solo aplica a la corrida
            // completa (sin ámbito). Si se pidió un canal concreto (onlyConnectionId,
            // "Sincronizar ahora") y no aparece —p. ej. se borró justo antes—
            // no hay nada que hacer: caer al legacy procesaría el buzón
            // equivocado.
            if (empty($connections)) {
                if ($this->onlyConnectionId !== null) {
                    return;
                }

                $this->fetchEmails();

                return;
            }

            $channels = app(TicketEmailChannelsRepository::class);

            foreach ($connections as $connection) {
                $createTickets = (bool) ($connection['create_tickets'] ?? false);
                $createReplies = (bool) ($connection['create_replies'] ?? false);

                // Sin ninguna acción habilitada no hay nada que hacer con este buzón.
                if (! $createTickets && ! $createReplies) {
                    continue;
                }

                $connectionId = $connection['id'] ?? null;

                try {
                    $this->processConnection($connection);

                    if ($connectionId) {
                        $channels->recordHealth($connectionId, success: true);
                    }
                } catch (\Throwable $e) {
                    Log::error('FetchTicketEmailsJob: error procesando conexión', [
                        'connection' => $connection['name'] ?? '?',
                        'error' => $e->getMessage(),
                    ]);

                    if ($connectionId) {
                        $channels->recordHealth($connectionId, success: false, error: $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching ticket emails: '.$e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Conexiones IMAP entrantes definidas en el setting `incoming_email`.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function incomingConnections(): array
    {
        // El blob 'incoming_email' se guarda cifrado (Setting::setEncrypted) desde
        // el fix de seguridad de MailsSettings — leerlo directo por DB::table()
        // devolvía el ciphertext crudo en vez del JSON. Setting::getDecrypted()
        // desencripta y también sirve las filas legacy sin cifrar tal cual.
        $raw = Setting::getDecrypted('incoming_email', '{}');

        if (! $raw) {
            return [];
        }

        $data = json_decode((string) $raw, true);
        $connections = $data['imap']['connections'] ?? [];

        if ($this->onlyConnectionId !== null) {
            return array_values(array_filter(
                $connections,
                fn ($connection) => ($connection['id'] ?? null) === $this->onlyConnectionId
            ));
        }

        return $connections;
    }

    /**
     * Conecta a un buzón IMAP concreto y procesa sus mensajes no leídos.
     *
     * IMPORTANTE: usa webklex/php-imap (única librería IMAP realmente
     * instalada en el proyecto). Este método antes instanciaba
     * `PhpImap\Mailbox` (paquete barbushin/php-imap), que nunca estuvo en
     * composer.json — cualquier canal con create_tickets/create_replies
     * activo hacía fallar el job entero con "Class not found" en cuanto
     * intentaba conectarse de verdad (nunca antes se había activado un canal
     * real hasta detectarlo).
     *
     * @param  array<string, mixed>  $connection
     */
    protected function processConnection(array $connection): void
    {
        $client = $this->makeImapClient(
            host: $connection['host'] ?? '',
            port: (int) ($connection['port'] ?? 993),
            encryption: $connection['encryption'] ?? 'ssl',
            username: $connection['username'] ?? '',
            password: $connection['password'] ?? '',
        );

        $client->connect();

        try {
            $folder = $client->getFolder($connection['folder'] ?? 'INBOX');
            $messages = $folder->query()->whereUnseen()->get();

            foreach ($messages as $message) {
                try {
                    $this->processIncomingEmail($message, $connection);
                    $message->setFlag('Seen');
                } catch (\Throwable $e) {
                    Log::error('Error processing email: '.$e->getMessage());
                }
            }
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Fetch emails from the legacy single-mailbox config (pre multi-canal).
     */
    protected function fetchEmails(): void
    {
        $config = config('helpdesk.email.imap');

        if (! $config || ! ($config['enabled'] ?? false)) {
            Log::info('IMAP email fetching is disabled');

            return;
        }

        $client = $this->makeImapClient(
            host: $config['server'] ?? '',
            port: (int) ($config['port'] ?? 993),
            encryption: $config['encryption'] ?? 'ssl',
            username: $config['username'] ?? '',
            password: $config['password'] ?? '',
        );

        try {
            $client->connect();

            $folder = $client->getFolder($config['folder'] ?? 'INBOX');
            $messages = $folder->query()->whereUnseen()->get();

            if ($messages->isEmpty()) {
                Log::info('No new emails to fetch');

                return;
            }

            foreach ($messages as $message) {
                try {
                    $this->processIncomingEmail($message);
                    $message->setFlag('Seen');
                } catch (\Exception $e) {
                    Log::error('Error processing email: '.$e->getMessage());

                    continue;
                }
            }

            $client->disconnect();
        } catch (\Exception $e) {
            Log::error('IMAP connection error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Construye y devuelve (sin conectar) un cliente IMAP de webklex.
     */
    protected function makeImapClient(string $host, int $port, string $encryption, string $username, string $password): ImapClient
    {
        $options = [
            'host' => $host,
            'port' => $port,
            'protocol' => 'imap',
            'encryption' => $encryption,
            'validate_cert' => true,
            'username' => $username,
            'password' => $password,
        ];

        // Servidores de correo con TLS antiguo. OpenSSL 3 (el del contenedor)
        // trae deshabilitados TLS 1.0 y 1.1 de fabrica, asi que contra un buzon
        // que solo hable esas versiones el handshake muere con
        // "SSL routines::unsupported protocol" y el canal entero queda sin leer.
        // Bajar el nivel de cifrado los readmite, y se hace SOLO para los hosts
        // que lo necesiten (config helpdesk.imap.legacy_tls_hosts): el resto
        // sigue exigiendo TLS moderno.
        //
        // La verificacion del certificado NO se toca: validate_cert sigue en
        // true, asi que un certificado invalido o suplantado sigue rechazandose.
        if ($this->needsLegacyTls($host)) {
            $options['ssl_options'] = ['ciphers' => 'DEFAULT@SECLEVEL=0'];
        }

        return (new ClientManager)->make($options);
    }

    /**
     * Si el host esta en la lista de servidores con TLS obsoleto.
     */
    protected function needsLegacyTls(string $host): bool
    {
        $hosts = (array) config('helpdesk.imap.legacy_tls_hosts', []);

        return in_array(mb_strtolower($host), array_map('mb_strtolower', $hosts), true);
    }

    /**
     * Process a single incoming email message.
     */
    protected function processIncomingEmail(ImapMessage $message, array $connection = []): void
    {
        // Parse email data
        $parsed = [
            'message_id' => $this->stringAttribute($message->message_id) ?: $this->generateMessageId(),
            'in_reply_to' => $this->stringAttribute($message->in_reply_to),
            'references' => $this->stringAttribute($message->references),
            'from' => $this->formatAddressAttribute($message->from),
            'to' => $this->formatAddressAttribute($message->to),
            'cc' => $this->formatAddressAttribute($message->cc),
            'bcc' => $this->formatAddressAttribute($message->bcc),
            'subject' => $this->stringAttribute($message->subject) ?: 'Sin asunto',
            'body_text' => $message->getTextBody() ?: '',
            'body_html' => $message->getHTMLBody() ?: null,
            'headers' => $this->extractHeaders($message),
            'raw_email' => $this->rawSource($message),
        ];

        // Extract attachments
        $parsed['attachments'] = $this->parseAttachments($message);

        // Find or create ticket
        $ticket = $this->findOrCreateTicket($parsed, $connection);

        if (! $ticket) {
            Log::warning('Could not create ticket for email: '.$parsed['subject']);

            return;
        }

        // Create TicketMail record
        $ticketMail = TicketMail::createFromInbound($parsed, $ticket);

        // Create a TicketItem for the timeline (customer message)
        $item = $ticket->items()->create([
            'type' => 'message',
            'author_id' => $ticket->customer_id,
            'body' => $parsed['body_text'],
            'html_body' => $parsed['body_html'],
            'is_internal' => false,
        ]);

        // Sin esto, ningún listener de MessageAdded corría para un correo
        // entrante real — TicketMessagingController/Agents\MessagesController/
        // SendScheduledRepliesCommand sí lo disparan al crear un TicketItem,
        // pero esta era la única vía de creación que no lo hacía. Afecta
        // tanto a RunAiSentimentAnalysis/UpdateTicketLastActivity como al
        // nuevo TranslateIncomingTicketMessage (detección de idioma del
        // cliente + traducción del mensaje entrante).
        MessageAdded::dispatch($item);

        // Link mail to item
        $ticketMail->update(['ticket_item_id' => $item->id]);

        // Update last message timestamp
        $ticket->update(['last_message_at' => now()]);

        Log::info("Email processed for ticket #{$ticket->ticket_number}");
    }

    /**
     * Find existing ticket or create new one for email.
     */
    protected function findOrCreateTicket(array $parsed, array $connection = []): ?Ticket
    {
        // Resolve the sender address up front: it is required both for ticket
        // threading verification and for customer lookup/creation.
        $rawFrom = $parsed['from'];
        if (empty($rawFrom)) {
            Log::warning('FetchTicketEmailsJob: email without From header, skipping', ['subject' => $parsed['subject']]);

            return null;
        }
        $fromEmail = $this->extractEmailAddress($rawFrom);

        // Sender blacklist: block by exact email or by domain (including
        // subdomains) ANTES de cualquier intento de hilado. Estaba después del
        // hilado por Message-ID, así que un remitente bloqueado que respondiera
        // a un hilo existente se colaba entero: no se puede decidir si un
        // correo entra sin haber mirado antes de quién viene.
        if ($fromEmail && ($blocked = TicketEmailBlacklist::matches($fromEmail))) {
            $blocked->registerMatch(
                $fromEmail,
                $parsed['subject'] ?? null,
                $parsed['body_html'] ?? null,
                $parsed['body_text'] ?? null,
            );
            Log::info("FetchTicketEmailsJob: email from {$fromEmail} discarded, sender is blacklisted (rule #{$blocked->id}).");

            return null;
        }

        // Clasificador de spam: complementa a la lista negra, que solo bloquea
        // remitentes YA conocidos. RETIENE en cuarentena, no descarta — un
        // falso positivo aquí es un cliente real cuyo correo desaparece sin
        // que nadie se entere. Ver SpamClassifierService.
        if ($fromEmail && app(SpamClassifierService::class)->quarantineIfSpam($fromEmail, $parsed)) {
            return null;
        }

        // Try to find by Message-ID threading first — In-Reply-To es el padre
        // inmediato; References es la cadena completa del hilo (RFC 5322) y
        // cubre el caso en que el cliente responde a un mensaje intermedio
        // que ya no es el último, o un cliente de correo que solo rellena
        // References y no In-Reply-To.
        //
        // La verificación de remitente aplica aquí igual que en el hilado por
        // asunto de abajo, y por el mismo motivo. Los Message-ID salientes no
        // son adivinables, pero sí circulan: basta con que el cliente reenvíe
        // el correo del helpdesk a un tercero para que ese tercero tenga la
        // cabecera y, respondiendo, escriba dentro de un ticket ajeno.
        $threadIds = array_filter(array_merge(
            [$parsed['in_reply_to']],
            $this->splitReferences($parsed['references'] ?? null),
        ));

        if ($threadIds !== []) {
            $existingMail = TicketMail::with('ticket.customer:id,email')
                ->whereIn('message_id', $threadIds)
                ->first();

            if ($existingMail?->ticket) {
                if ($this->senderMatchesTicket($existingMail->ticket, $fromEmail)) {
                    return $existingMail->ticket;
                }

                Log::warning('FetchTicketEmailsJob: Message-ID thread sender does not match ticket customer, not threading', [
                    'ticket_number' => $existingMail->ticket->ticket_number,
                    'from' => $fromEmail,
                ]);
            }
        }

        // Try to find by ticket number in subject (e.g., "Re: Ticket #TCK-2025-00123").
        // Only thread into the ticket when the sender matches the ticket customer,
        // otherwise a third party could inject messages into someone else's ticket.
        if (preg_match('/#(TCK-\d{4}-\d{5})/', $parsed['subject'], $matches)) {
            $ticket = Ticket::with('customer:id,email')->where('ticket_number', $matches[1])->first();
            if ($ticket && $this->senderMatchesTicket($ticket, $fromEmail)) {
                return $ticket;
            }

            if ($ticket) {
                Log::warning('FetchTicketEmailsJob: sender does not match ticket customer, creating new ticket', [
                    'ticket_number' => $matches[1],
                    'from' => $fromEmail,
                ]);
            }
        }

        // Llegados aquí no se pudo enlazar con un ticket existente, así que
        // habría que CREAR uno nuevo. Si esta conexión no permite crear tickets
        // (solo respuestas), no se crea: se devuelve null y el email se ignora.
        // Sin conexión (fallback de buzón único) se mantiene el comportamiento previo.
        if ($connection !== [] && ! ($connection['create_tickets'] ?? false)) {
            return null;
        }

        $customer = Customer::where('email', $fromEmail)->first();

        if (! $customer) {
            // Create new customer
            $fromName = $this->extractEmailName($parsed['from']);
            $customer = Customer::create([
                'email' => $fromEmail,
                'name' => $fromName ?: $fromEmail,
            ]);
            Log::info("Created new customer: {$fromEmail}");
        }

        // Vincula el cliente con el ERP por email, en segundo plano — mismo
        // mecanismo que ConversationCreated → DispatchErpLinkJob →
        // LinkCustomerToErpJob en el núcleo Helpdesk (helpdesk_erp_enabled()
        // respeta el toggle de Settings → Integraciones). LinkCustomerToErpJob
        // ya es idempotente (no repite si el cliente ya tiene id_cliente
        // vinculado) y best-effort: si el email no existe en el ERP, o el ERP
        // no responde, simplemente no se vincula — nunca bloquea ni descarta
        // el ticket. class_exists() porque HelpdeskErp es un módulo aparte que
        // puede no estar instalado.
        if (helpdesk_erp_enabled() && class_exists(LinkCustomerToErpJob::class)) {
            LinkCustomerToErpJob::dispatch($customer->id);
        }

        // Create new ticket inside a transaction so the lockForUpdate in
        // generateTicketNumber() is effective and numbers never collide.
        $ticket = DB::transaction(fn () => Ticket::create([
            'customer_id' => $customer->id,
            'subject' => $parsed['subject'],
            'description' => $parsed['body_text'] ?? $parsed['body_html'],
            'source' => 'email',
            'status_id' => TicketStatus::where('is_default', true)->first()?->id ?? 1,
            'priority' => $this->detectPriority($parsed['subject']),
            // Explícito (no depender del TicketObserver::creating): el número se
            // genera aquí, dentro de la transacción que hace efectivo el lockForUpdate.
            'ticket_number' => Ticket::generateTicketNumber(),
        ]));

        Log::info("Created new ticket #{$ticket->ticket_number} from email");

        // Sin esto, TicketCreated nunca se disparaba para un ticket nacido de
        // un correo real: SendCustomerConfirmation (correo "hemos recibido tu
        // solicitud"), NotifyAgentsOnNewTicket, RunAiAutoClassify, etc. están
        // suscritos a este evento pero solo lo reciben cuando el ticket se
        // crea vía TicketService::createTicket() (widget/formulario público),
        // nunca desde este job — el único canal real de entrada de tickets no
        // avisaba al cliente que su solicitud había llegado. Mismo patrón que
        // el MessageAdded::dispatch() de más arriba.
        TicketCreated::dispatch($ticket);

        return $ticket;
    }

    /**
     * Determine whether the sender address belongs to the ticket customer.
     */
    protected function senderMatchesTicket(Ticket $ticket, string $fromEmail): bool
    {
        $customerEmail = $ticket->customer?->email;

        if (! $customerEmail) {
            return false;
        }

        return strcasecmp(trim($customerEmail), trim($fromEmail)) === 0;
    }

    /**
     * Parse attachments from email message.
     */
    protected function parseAttachments(ImapMessage $message): array
    {
        $attachments = [];

        foreach ($message->getAttachments() as $attachment) {
            try {
                $filename = $attachment->name ?? 'attachment';
                $filePath = $this->saveAttachment($attachment);

                if ($filePath) {
                    $absolutePath = storage_path('app/'.$filePath);
                    $size = null;

                    try {
                        $size = filesize($absolutePath) ?: null;
                    } catch (\Throwable) {
                        // File may not be readable; size remains null
                    }

                    $attachments[] = [
                        'filename' => $filename,
                        'url' => asset('storage/'.$filePath),
                        'size' => $size,
                        'mime' => mime_content_type($absolutePath),
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Error processing attachment: '.$e->getMessage());
            }
        }

        return $attachments;
    }

    /**
     * Save attachment to storage.
     */
    protected function saveAttachment(ImapAttachment $attachment): ?string
    {
        try {
            $filename = $attachment->name ?? time().'_'.random_int(1000, 9999);

            $allowedExtensions = config('helpdesk.attachments.allowed_extensions', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'zip']);
            $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

            if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
                Log::warning('FetchTicketEmailsJob: skipped attachment with disallowed extension', [
                    'filename' => $filename,
                ]);

                return null;
            }

            $basePath = config('helpdesk.attachments.path', 'helpdesk/attachments');
            $path = $basePath.'/'.date('Y/m/d').'/'.$filename;

            // Save to storage
            Storage::disk('local')->put($path, $attachment->getContent());

            return $path;
        } catch (\Exception $e) {
            Log::error('Error saving attachment: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Extract email address from name+email format.
     */
    protected function extractEmailAddress(string $from): string
    {
        // Handle "Name <email@domain.com>" format
        if (preg_match('/<(.+?)>/', $from, $matches)) {
            return $matches[1];
        }

        // Return as-is if already just email
        return trim($from);
    }

    /**
     * Extract name from name+email format.
     */
    protected function extractEmailName(string $from): ?string
    {
        // Handle "Name <email@domain.com>" format
        if (preg_match('/^(.+?)\s*</', $from, $matches)) {
            return trim($matches[1], ' "\'');
        }

        return null;
    }

    /**
     * Extract important headers from message.
     */
    protected function extractHeaders(ImapMessage $message): array
    {
        return [
            'Message-ID' => $this->stringAttribute($message->message_id),
            'In-Reply-To' => $this->stringAttribute($message->in_reply_to),
            'References' => $this->stringAttribute($message->references),
            'Subject' => $this->stringAttribute($message->subject),
            'Date' => $this->stringAttribute($message->date),
        ];
    }

    /**
     * Convierte un Attribute de webklex de un header simple (no de dirección,
     * p. ej. Message-ID/Subject/Date) a string plano, o null si no hay valor.
     * Attribute::__toString() ya hace implode(", ", $values) — seguro para
     * estos headers porque sus valores son escalares, a diferencia de
     * from/to/cc/bcc (ver formatAddressAttribute()).
     */
    /**
     * @return array<int, string>
     */
    protected function splitReferences(?string $references): array
    {
        if (! $references) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($id) => trim($id, " \t\n\r\0\x0B<>"),
            explode(',', $references)
        )));
    }

    protected function stringAttribute(?ImapAttribute $attribute): ?string
    {
        if ($attribute === null) {
            return null;
        }

        $value = (string) $attribute;

        return $value !== '' ? $this->decodeMimeHeader($value) : null;
    }

    /**
     * Fallback de decodificación MIME (RFC 2047, "=?utf-8?b?...?="). El
     * decoder interno de webklex/php-imap (Decoder\HeaderDecoder) puede dejar
     * el asunto/nombre sin decodificar cuando ni ext-imap ni su propio
     * mimeHeaderDecode() lo resuelven (confirmado en este entorno, sin
     * ext-imap: un asunto real de Hostinger llegaba como
     * "=?utf-8?b?V2hhdOKAmXM=?= new for developers..." en vez de "What's
     * new..."). mb_decode_mimeheader() no requiere extensiones y es un no-op
     * seguro sobre texto que ya está plano.
     */
    protected function decodeMimeHeader(string $value): string
    {
        return str_contains($value, '=?') ? mb_decode_mimeheader($value) : $value;
    }

    /**
     * Convierte un Attribute de dirección (from/to/cc/bcc) al formato
     * "Nombre <email>" (o solo "email" sin nombre) que ya espera
     * extractEmailAddress()/extractEmailName() — no se puede usar
     * Attribute::__toString() aquí porque para estos headers el Attribute
     * envuelve un array de objetos {personal, mailbox, host}, no strings.
     * Varias direcciones se unen con ", " (mismo criterio que barbushin/php-imap).
     */
    protected function formatAddressAttribute(?ImapAttribute $attribute): ?string
    {
        if ($attribute === null) {
            return null;
        }

        $formatted = collect($attribute->all())
            ->map(function ($address) {
                $email = trim(($address->mailbox ?? '').'@'.($address->host ?? ''), '@');
                $personal = $this->decodeMimeHeader(trim((string) ($address->personal ?? '')));

                if ($email === '') {
                    return null;
                }

                return $personal !== '' ? "{$personal} <{$email}>" : $email;
            })
            ->filter()
            ->implode(', ');

        return $formatted !== '' ? $formatted : null;
    }

    /**
     * Fuente cruda del correo (headers + cuerpo), igual al patrón interno de
     * Message::save() en webklex/php-imap.
     */
    protected function rawSource(ImapMessage $message): ?string
    {
        $raw = ($message->getHeader()?->raw ?? '')."\r\n\r\n".$message->getRawBody();

        return trim($raw) !== '' ? $raw : null;
    }

    /**
     * Detect priority from subject keywords.
     */
    protected function detectPriority(string $subject): string
    {
        $subject = strtolower($subject);

        if (str_contains($subject, 'urgent') || str_contains($subject, 'crítico')) {
            return 'urgent';
        }

        if (str_contains($subject, 'baja') || str_contains($subject, 'low')) {
            return 'low';
        }

        return 'normal';
    }

    /**
     * Generate a unique Message-ID (fallback para el raro caso de un correo
     * entrante sin su propio Message-ID). Sin '<' '>' — mismo criterio que el
     * resto de generadores de message_id del módulo: stringAttribute() ya
     * guarda los Message-ID/In-Reply-To/References entrantes normalizados sin
     * corchetes (los quita webklex/php-imap), así que este fallback debe
     * coincidir en formato para no romper el enganche por comparación exacta.
     */
    protected function generateMessageId(): string
    {
        return uniqid().'@'.config('app.name');
    }
}
