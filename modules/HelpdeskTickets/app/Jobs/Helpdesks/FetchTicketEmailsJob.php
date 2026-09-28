<?php

namespace Modules\HelpdeskTickets\Jobs\Helpdesks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository;
use Modules\HelpdeskTickets\Services\TicketService;
use Modules\HelpdeskTickets\Support\EmailReplyQuoteStripper;
use Modules\HelpdeskTickets\Support\InboundEmailAttachmentStorer;
use Modules\HelpdeskTickets\Support\InboundEmailBounceRouter;
use Modules\HelpdeskTickets\Support\InboundEmailMessageParser;
use Modules\HelpdeskTickets\Support\InboundEmailTicketResolver;
use Webklex\PHPIMAP\Client as ImapClient;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message as ImapMessage;

/**
 * Lectura de buzones IMAP y creación/hilado de tickets a partir del correo
 * entrante. El parseo de mensajes (InboundEmailMessageParser), el guardado de
 * adjuntos (InboundEmailAttachmentStorer), el hilado/creación de tickets
 * (InboundEmailTicketResolver) y la detección de rebotes/quejas
 * (InboundEmailBounceRouter) viven en app/Support (28-sep-2026, job de 1103
 * líneas) — este job se queda con la orquestación IMAP (conexión, colas,
 * reintentos) y llama a esas clases directamente a través de los
 * colaboradores perezosos de más abajo.
 *
 * Troceado otra vez (28-sep-2026): los ~20 métodos protegidos que antes
 * delegaban uno a uno en esas clases (mismo nombre/firma que sus métodos
 * públicos) solo existían para que los tests pudieran ejercitarlos por
 * subclase/ReflectionMethod — el job en sí ya llamaba a los colaboradores a
 * través de ellos, nunca los necesitó como API propia. Se eliminaron y
 * processIncomingEmail() llama a InboundEmailMessageParser/
 * InboundEmailAttachmentStorer/InboundEmailTicketResolver/
 * InboundEmailBounceRouter directamente; los tests que los ejercitaban ahora
 * apuntan a esas clases (ver tests/Unit/Support/).
 */
class FetchTicketEmailsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    /**
     * Una sola lectura del buzón a la vez.
     *
     * Antes esto lo hacía el withoutOverlapping() de la tarea programada, pero
     * ahí protegía lo que no toca: el comando solo despacha este trabajo y
     * termina en milisegundos. Con la lectura cada pocos segundos, ese cerrojo
     * se quedaba tomado y la bandeja dejaba de leer correo durante minutos
     * —reproducido varias veces el 7-sep-2026—. El solape de verdad puede
     * ocurrir aquí, leyendo el mismo buzón dos veces a la vez, y aquí es donde
     * se evita.
     *
     * uniqueFor corto para que una caída a media lectura no deje el cerrojo
     * tomado más de un minuto; el trabajo entero tarda menos de un segundo.
     */
    public int $uniqueFor = 60;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    protected ?TicketService $ticketService = null;

    private ?InboundEmailMessageParser $emailParser = null;

    private ?InboundEmailAttachmentStorer $attachmentStorer = null;

    private ?InboundEmailTicketResolver $ticketResolver = null;

    private ?InboundEmailBounceRouter $bounceRouter = null;

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
                if (isset($this->onlyConnectionId)) {
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

        // isset() y no !== null: $onlyConnectionId es una propiedad tipada con
        // default null promovida por constructor. Illuminate\Queue\SerializesModels
        // omite del payload cualquier propiedad cuyo valor sea igual a su default
        // (optimizacion de tamano) — como el dispatch normal (sin ambito) siempre
        // deja este valor en null, nunca viaja serializada, y __unserialize() jamas
        // la toca: queda SIN INICIALIZAR en el job reconstruido por el worker, no en
        // null. Leerla con !== null explota con "must not be accessed before
        // initialization" en cuanto el job pasa de verdad por la cola (no se via
        // hasta ahora porque Horizon llevaba tiempo caido). isset() sobre una
        // propiedad tipada sin inicializar da false sin lanzar, que es exactamente
        // la semantica que se busca aqui (sin ambito = procesar todos los canales).
        if (isset($this->onlyConnectionId)) {
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
            $query = $folder->query()->whereUnseen();

            // Punto de corte configurado en el canal ("Sincronizar desde"):
            // criterio IMAP SINCE, filtra por dia completo (sin hora) del lado
            // del servidor, antes de traer un solo mensaje.
            if (! empty($connection['sync_since'])) {
                $query->whereSince($connection['sync_since']);
            }

            $messages = $query->get();

            foreach ($messages as $message) {
                $this->processMessage($message, $connection);
            }
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Procesa un mensaje IMAP y marca 'Seen' en dos pasos separados, no un
     * único try/catch: si processIncomingEmail() falla, el mensaje debe
     * quedar sin leer para reintentarse entero en la próxima corrida (mismo
     * comportamiento que antes). Si processIncomingEmail() tiene éxito pero
     * setFlag('Seen') falla (visto en producción: error de IMAP silencioso),
     * el mensaje seguiría apareciendo como no leído y se reprocesaría cada
     * minuto — el guard de idempotencia al inicio de processIncomingEmail()
     * (por message_id o UID IMAP) es quien evita que eso vuelva a crear un
     * ticket duplicado o reenvíe la confirmación al cliente; aquí solo se
     * distingue el log para que ese caso no se confunda con un fallo real de
     * procesamiento.
     */
    protected function processMessage(ImapMessage $message, array $connection): void
    {
        try {
            $this->processIncomingEmail($message, $connection);
        } catch (\Throwable $e) {
            Log::error('Error processing email: '.$e->getMessage());

            return;
        }

        try {
            $message->setFlag('Seen');
        } catch (\Throwable $e) {
            Log::warning('FetchTicketEmailsJob: email processed but failed to mark as Seen, will retry the flag next run', [
                'error' => $e->getMessage(),
            ]);
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
                $this->processMessage($message, []);
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
        // Idempotencia por Message-ID/UID IMAP: si este correo ya se guardó
        // en un TicketMail, reprocesarlo (típicamente porque setFlag('Seen')
        // falló y el mensaje sigue apareciendo como no leído) NO debe crear
        // un segundo ticket ni reenviar la confirmación al cliente. Algunos
        // emisores omiten Message-ID; en ese caso usamos el UID estable del
        // buzón, con el canal/carpeta dentro del namespace.
        $messageId = $this->emailParser()->stringAttribute($message->message_id);
        if (! $messageId) {
            $messageId = $this->emailParser()->stableImapMessageId($message, $connection);
        }

        if ($messageId && TicketMail::withTrashed()->where('message_id', $messageId)->exists()) {
            Log::info('FetchTicketEmailsJob: email already processed, skipping (message_id already recorded)', [
                'message_id' => $messageId,
            ]);

            return;
        }

        // Un canal de Tickets no tiene un buzón de rebotes dedicado propio
        // (a diferencia de Document) — un DSN por un envío fallido de este
        // mismo canal rebota a esta misma bandeja, la única que ya se
        // sondea. Sin este chequeo, el DSN se convertía en un ticket basura
        // con el contenido técnico del rebote como si fuera un mensaje real
        // del cliente. No hay riesgo de falso positivo grave: como mucho un
        // DSN legítimo no se detecta y sigue el flujo normal de ticket.
        if ($this->bounceRouter()->routeIfBounceOrComplaint($message)) {
            return;
        }

        // Parse email data
        $parsed = [
            'message_id' => $messageId ?: $this->emailParser()->generateMessageId(),
            'in_reply_to' => $this->emailParser()->stringAttribute($message->in_reply_to),
            'references' => $this->emailParser()->stringAttribute($message->references),
            'from' => $this->emailParser()->formatAddressAttribute($message->from),
            'to' => $this->emailParser()->formatAddressAttribute($message->to),
            'cc' => $this->emailParser()->formatAddressAttribute($message->cc),
            'bcc' => $this->emailParser()->formatAddressAttribute($message->bcc),
            'subject' => $this->emailParser()->stringAttribute($message->subject) ?: 'Sin asunto',
            'body_text' => $message->getTextBody() ?: '',
            'body_html' => $message->getHTMLBody() ?: null,
            'headers' => $this->emailParser()->extractHeaders($message),
            'raw_email' => $this->emailParser()->rawSource($message),
        ];

        // Extract attachments
        $skippedAttachments = [];
        $parsed['attachments'] = $this->attachmentStorer()->parseAttachments($message, $skippedAttachments);

        // Find or create ticket
        $ticket = $this->ticketResolver()->findOrCreateTicket($parsed, $connection);

        if (! $ticket) {
            Log::warning('Could not create ticket for email: '.$parsed['subject']);

            return;
        }

        ($this->ticketService ?? app(TicketService::class))->reopenIfCustomerCanReopen($ticket);

        // Create TicketMail record. Con el body_html/body_text COMPLETOS,
        // sin recortar -- es el registro de auditoría ("Correo"/"Ver
        // original" en el panel), tiene que conservar el correo tal cual
        // llegó.
        try {
            $ticketMail = TicketMail::createFromInbound($parsed, $ticket);
        } catch (QueryException $e) {
            // El guard anterior cubre el caso normal. Este segundo cierre es
            // necesario si dos workers pasan el guard al mismo tiempo: el
            // índice UNIQUE de message_id gana la carrera y el perdedor debe
            // tratarse como ya procesado, no quedar reintentándose para
            // siempre con el correo aún marcado como no leído.
            if ($messageId && TicketMail::withTrashed()->where('message_id', $messageId)->exists()) {
                Log::warning('FetchTicketEmailsJob: duplicate email rejected by unique message_id index, skipping', [
                    'message_id' => $messageId,
                ]);

                return;
            }

            throw $e;
        }

        // Create a TicketItem for the timeline (customer message). Los
        // adjuntos entran por attachment_urls (rutas de storage), el mismo
        // campo que ya usan las subidas del panel de agente — así el tab
        // "Adjuntos" del ticket (TicketDetailDataController) y la descarga
        // autorizada (TicketAttachmentDownloadController::download()) los
        // sirven sin código nuevo.
        //
        // Aquí SÍ se recorta la cita del correo anterior (ver
        // EmailReplyQuoteStripper): esto es lo que se ve en el hilo del
        // ticket, y antes enseñaba el HTML completo de la plantilla citada
        // (tablas, estilos inline, el logo...) como si fuera parte de lo que
        // escribió el cliente (detectado 3-sep-2026, TCK-2026-00093, cliente
        // respondiendo desde Gmail).
        $item = $ticket->items()->create([
            'type' => 'message',
            'author_id' => $ticket->customer_id,
            'body' => EmailReplyQuoteStripper::stripText($parsed['body_text']),
            'html_body' => EmailReplyQuoteStripper::stripHtml($parsed['body_html']),
            'is_internal' => false,
            'attachment_urls' => array_column($parsed['attachments'], 'path'),
        ]);

        // Sin esto, ningún listener de MessageAdded corría para un correo
        // entrante real — TicketMessagingController/Agents\MessagesController/
        // SendScheduledRepliesCommand sí lo disparan al crear un TicketItem,
        // pero esta era la única vía de creación que no lo hacía. Afecta
        // tanto a RunAiSentimentAnalysis/UpdateTicketLastActivity como al
        // nuevo TranslateIncomingTicketMessage (detección de idioma del
        // cliente + traducción del mensaje entrante).
        MessageAdded::dispatch($item);

        if ($skippedAttachments !== []) {
            $ticket->items()->create([
                'type' => 'system',
                'is_internal' => true,
                'body' => (count($skippedAttachments) === 1 ? 'No se guardó un adjunto' : 'No se guardaron '.count($skippedAttachments).' adjuntos')
                    .' de este correo por tener una extensión no permitida: '.implode(', ', $skippedAttachments).'.',
            ]);
        }

        // Link mail to item
        $ticketMail->update(['ticket_item_id' => $item->id]);

        // Update last message timestamp
        $ticket->update(['last_message_at' => now()]);

        Log::info("Email processed for ticket #{$ticket->ticket_number}");
    }

    /**
     * Colaboradores instanciados perezosamente (no en el constructor): así
     * nunca viajan en el payload serializado del job (siguen null en el
     * momento del dispatch, igual que $ticketService), y un worker en
     * ejecución con la clase vieja (deploy en curso) puede seguir
     * deserializando trabajos ya encolados sin pedir estas dependencias.
     */
    private function emailParser(): InboundEmailMessageParser
    {
        return $this->emailParser ??= app(InboundEmailMessageParser::class);
    }

    private function attachmentStorer(): InboundEmailAttachmentStorer
    {
        return $this->attachmentStorer ??= app(InboundEmailAttachmentStorer::class);
    }

    private function ticketResolver(): InboundEmailTicketResolver
    {
        return $this->ticketResolver ??= app(InboundEmailTicketResolver::class);
    }

    private function bounceRouter(): InboundEmailBounceRouter
    {
        return $this->bounceRouter ??= app(InboundEmailBounceRouter::class);
    }
}
