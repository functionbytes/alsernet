<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Config;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;

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
    public function __construct(private readonly TicketEmailChannelsRepository $channels) {}

    /**
     * @return array<string, mixed>|null
     */
    public function resolveChannelForTicket(Ticket $ticket): ?array
    {
        $lastInbound = TicketMail::where('ticket_id', $ticket->id)
            ->where('direction', 'inbound')
            ->latest()
            ->first();

        if (! $lastInbound || empty($lastInbound->to)) {
            return null;
        }

        $to = strtolower($lastInbound->to);

        foreach ($this->channels->all() as $channel) {
            $username = strtolower((string) ($channel['username'] ?? ''));

            if ($username !== '' && str_contains($to, $username)) {
                return $channel;
            }
        }

        return null;
    }

    /**
     * Message-ID del último correo entrante del ticket (para In-Reply-To/
     * References de la respuesta saliente). Null si el ticket no vino nunca
     * por email o si ese mensaje no tenía Message-ID.
     */
    public function lastInboundMessageId(Ticket $ticket): ?string
    {
        return TicketMail::where('ticket_id', $ticket->id)
            ->where('direction', 'inbound')
            ->latest()
            ->value('message_id');
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

        // Mismo apaño que en FetchTicketEmailsJob para los servidores que solo
        // hablan TLS 1.0/1.1: OpenSSL 3 los rechaza de fabrica y el envio muere
        // con "unsupported protocol". Se rebaja el nivel de cifrado solo para
        // los hosts declarados en helpdesk.imap.legacy_tls_hosts, y sin tocar la
        // verificacion del certificado, que sigue exigiendose.
        if ($this->needsLegacyTls((string) $channel['smtp_host'])) {
            $mailer['stream'] = [
                'ssl' => [
                    'ciphers' => 'DEFAULT@SECLEVEL=0',
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ];
        }

        Config::set("mail.mailers.{$name}", $mailer);

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
