<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

/**
 * Resuelve por qué canal de correo (buzón IMAP/SMTP) debería salir la
 * respuesta a un ticket, y registra un mailer Laravel dinámico apuntando al
 * SMTP de ese canal — así el agente responde de verdad desde la misma
 * dirección a la que el cliente escribió, en vez de siempre usar el mailer
 * global de la app (bug real encontrado: SendCustomerReplyNotification
 * mandaba todo con config('mail.from.address'), sin relación con el canal).
 *
 * No hay columna `ticket.email_channel_id` (los tickets pueden venir de
 * fuentes no-email); en vez de migrar el esquema, se identifica el canal por
 * el campo `to` del último TicketMail entrante del ticket, que ya guarda la
 * dirección exacta a la que llegó el correo (p. ej. info@functionbytes.com),
 * cruzada contra el `username` de cada canal configurado.
 */
class TicketChannelMailerService
{
    /**
     * Memoizadas por ticket_id (no por instancia: SendCustomerConfirmation/
     * StatusNotification/ReopenNotification/ReplyNotification son listeners
     * ShouldQueue independientes, cada uno resuelve su PROPIA instancia de
     * este servicio vía el contenedor — un array de instancia no serviría de
     * nada entre ellos). El worker que sirve la cola 'notifications'
     * (supervisor-helpdesk, maxProcesses:1) procesa esos jobs uno tras otro
     * en el MISMO proceso PHP, así que hasta 4 listeners reaccionando al
     * mismo cambio de estado de un ticket pagaban hasta 3 queries a
     * helpdesk_ticket_mails cada uno — 12 queries para un solo evento
     * (14-sep-2026, auditoría de rendimiento). El canal/hilo de un ticket no
     * cambia dentro de esa ventana, así que memoizar es seguro.
     *
     * @var array<int, array<string, mixed>|null>
     */
    private static array $channelCache = [];

    /** @var array<int, string|null> */
    private static array $lastInboundMessageIdCache = [];

    /** @var array<int, string|null> */
    private static array $firstMailSubjectCache = [];

    public function __construct(private readonly TicketEmailChannelsRepository $channels) {}

    /**
     * @return array<string, mixed>|null
     */
    public function resolveChannelForTicket(Ticket $ticket): ?array
    {
        if (array_key_exists($ticket->id, self::$channelCache)) {
            return self::$channelCache[$ticket->id];
        }

        $lastInbound = TicketMail::where('ticket_id', $ticket->id)
            ->where('direction', 'inbound')
            ->latest()
            ->first();

        if ($lastInbound && ! empty($lastInbound->to)) {
            $to = strtolower($lastInbound->to);

            foreach ($this->channels->all() as $channel) {
                $username = strtolower((string) ($channel['username'] ?? ''));

                if ($username !== '' && str_contains($to, $username)) {
                    return self::$channelCache[$ticket->id] = $channel;
                }
            }
        }

        // Sin correo entrante que correlacionar (ticket nacido de un
        // formulario web, el widget, o creado a mano desde el panel): cae al
        // canal marcado como "canal por defecto", si hay uno configurado.
        // Antes esto devolvía siempre null, y la confirmación "hemos
        // recibido tu solicitud" salía del mailer genérico de la app en vez
        // del buzón real de soporte (detectado 3-sep-2026 probando un ticket
        // real nacido del formulario de contacto de alsernetforms).
        return self::$channelCache[$ticket->id] = $this->channels->default();
    }

    /**
     * Message-ID del último correo entrante del ticket (para In-Reply-To/
     * References de la respuesta saliente). Null si el ticket no vino nunca
     * por email o si ese mensaje no tenía Message-ID.
     */
    public function lastInboundMessageId(Ticket $ticket): ?string
    {
        if (array_key_exists($ticket->id, self::$lastInboundMessageIdCache)) {
            return self::$lastInboundMessageIdCache[$ticket->id];
        }

        return self::$lastInboundMessageIdCache[$ticket->id] = TicketMail::where('ticket_id', $ticket->id)
            ->where('direction', 'inbound')
            ->latest()
            ->value('message_id');
    }

    /**
     * Asunto anclado al de la PRIMERA fila real de correo del ticket (casi
     * siempre la confirmación "Hemos recibido tu solicitud"), no al campo
     * ticket.subject.
     *
     * Bug real encontrado probando el flujo en vivo (4-sep-2026, TCK-2026-00093):
     * la confirmación usa un asunto fijo de plantilla ("Hemos recibido tu
     * solicitud — #TCK-..."), pero SendCustomerReplyNotification/
     * SendCustomerStatusNotification/SendCustomerReopenNotification/
     * TicketCommentsController construían el suyo desde ticket.subject
     * ("Contacto general" o cualquier otro valor) — con In-Reply-To/References
     * correctos pero el asunto completamente distinto, Gmail abría un hilo
     * NUEVO en cada aviso automático en vez de seguir la conversación (visto
     * con un correo real: la respuesta del agente llegó como "Re: Contacto
     * general" en un hilo aparte, mientras el cliente seguía respondiendo al
     * hilo original "Hemos recibido tu solicitud").
     */
    public function threadSubject(Ticket $ticket): string
    {
        // Solo se memoiza la parte que cuesta una query (el asunto guardado
        // del primer TicketMail); el resto se recalcula siempre con los
        // datos ACTUALES de $ticket, para no arrastrar un subject/
        // ticket_number obsoleto de una llamada anterior con una instancia
        // más vieja del mismo ticket.
        if (! array_key_exists($ticket->id, self::$firstMailSubjectCache)) {
            self::$firstMailSubjectCache[$ticket->id] = TicketMail::where('ticket_id', $ticket->id)->oldest()->value('subject');
        }

        $stored = self::$firstMailSubjectCache[$ticket->id];

        $base = $stored ? $this->stripThreadDecorations($ticket, $stored) : null;

        return 'Re: '.($base ?: ($ticket->subject ?: 'tu ticket')).' — #'.$ticket->ticket_number;
    }

    /**
     * Quita un "Re:"/"Fwd:" inicial y el "— #TCK-..." final ya presentes en
     * un asunto guardado, para no acabar con "Re: Re: asunto — #TCK-1 — #TCK-1".
     */
    private function stripThreadDecorations(Ticket $ticket, string $subject): ?string
    {
        $subject = trim($subject);
        $subject = preg_replace('/^(re|fwd?)\s*:\s*/i', '', $subject) ?? $subject;
        // /u obligatorio: '—' (em dash, U+2014) es multibyte en UTF-8 — sin el
        // modificador, la clase [—-] la parte en bytes sueltos y el patrón
        // deja un byte huérfano en vez de consumir el guion entero (mismo
        // gotcha ya documentado con [oó] en otro módulo).
        $subject = preg_replace('/\s*[—-]\s*#'.preg_quote((string) $ticket->ticket_number, '/').'\s*$/iu', '', $subject) ?? $subject;
        $subject = trim($subject);

        return $subject !== '' ? $subject : null;
    }

    /**
     * Registra (si hace falta) un mailer dinámico para este canal y devuelve
     * su nombre, listo para Mail::mailer($name)->... Devuelve null si el
     * canal no tiene SMTP configurado (buzones dados de alta antes de este
     * fix, o solo-lectura) — el llamador debe caer al mailer por defecto.
     *
     * @param  array<string, mixed>  $channel
     */
    public function mailerNameFor(array $channel): ?string
    {
        if (empty($channel['smtp_host']) || empty($channel['username']) || empty($channel['password'])) {
            return null;
        }

        // Nombre estable por canal (no por request) para que Laravel reutilice
        // la misma conexión SMTP entre envíos dentro del mismo proceso/worker.
        $name = 'ticket_channel_'.md5((string) $channel['id']);

        $mailer = [
            'transport' => 'smtp',
            'host' => $channel['smtp_host'],
            'port' => $channel['smtp_port'] ?? 465,
            'encryption' => $channel['smtp_encryption'] ?? 'ssl',
            'username' => $channel['username'],
            'password' => $channel['password'],
        ];

        $streamOptions = null;

        // Mismo apaño que en FetchTicketEmailsJob para los servidores que solo
        // hablan TLS 1.0/1.1: OpenSSL 3 los rechaza de fabrica y el envio muere
        // con "unsupported protocol". Se rebaja el nivel de cifrado solo para
        // los hosts declarados en helpdesk.imap.legacy_tls_hosts, y sin tocar la
        // verificacion del certificado, que sigue exigiendose.
        if ($this->needsLegacyTls((string) $channel['smtp_host'])) {
            $streamOptions = [
                'ssl' => [
                    'ciphers' => 'DEFAULT@SECLEVEL=0',
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ];
        }

        Config::set("mail.mailers.{$name}", $mailer);

        // Config::set(...) por sí solo NO alcanza para el apaño de arriba:
        // MailManager::configureSmtpTransport() (Laravel 12) no lee ninguna
        // clave 'stream' del config array, así que guardarla ahí es un no-op
        // silencioso — verificado con un envío real contra correo.a-alvarez.com,
        // que fallaba con el mismo "unsupported protocol" pese a esta config.
        // Symfony sí expone setStreamOptions() en el SocketStream del
        // transporte ya construido, así que se aplica acá, sobre la instancia
        // que Mail::mailer($name) cachea internamente (MailManager::mailer()
        // reutiliza la misma instancia entre llamadas con el mismo $name),
        // para que el envío real que haga el llamador ya la tenga puesta.
        if ($streamOptions !== null) {
            $transport = Mail::mailer($name)->getSymfonyTransport();

            if ($transport instanceof EsmtpTransport && ($stream = $transport->getStream()) instanceof SocketStream) {
                $stream->setStreamOptions($streamOptions);
            }
        }

        return $name;
    }

    /**
     * Si el host SMTP esta en la lista de servidores con TLS obsoleto.
     */
    private function needsLegacyTls(string $host): bool
    {
        $hosts = (array) config('helpdesk.imap.legacy_tls_hosts', []);

        return in_array(mb_strtolower($host), array_map('mb_strtolower', $hosts), true);
    }
}
