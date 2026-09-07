<?php

namespace Modules\HelpdeskEmailActivity\Support;

/**
 * Parseo por regex de un DSN (Delivery Status Notification / bounce) o una
 * queja de spam a partir del asunto + cuerpo crudo de un mensaje IMAP — sin
 * librería de parseo RFC 3464 (no hay ninguna instalada en el proyecto).
 * Compartido por dos consumidores que necesitan detectar/parsear lo mismo:
 *
 *  - BounceProcessorService: lee un buzón DEDICADO de rebotes.
 *  - FetchTicketEmailsJob: un DSN puede llegar mezclado con correo normal de
 *    un canal de HelpdeskTickets (mismo buzón que ya sondea cada 10 min) —
 *    necesita reconocerlo para desviarlo aquí en vez de crear un ticket
 *    basura con el contenido del rebote.
 *
 * Puramente funciones puras de texto — no toca la base de datos ni conecta a
 * ningún sitio, eso es responsabilidad de cada consumidor.
 */
class DsnMessageParser
{
    /**
     * Heurística barata para decidir si un mensaje entrante TIENE FORMA de
     * DSN/queja antes de gastar el resto del parseo — basada en el asunto
     * (patrón universal de casi todos los MTA) más un par de cabeceras/
     * frases que solo aparecen en un DSN real, para no confundir un correo
     * legítimo que solo mencione la palabra "delivery" en el asunto.
     */
    public static function looksLikeBounceOrComplaint(string $subject, string $rawBody): bool
    {
        if (preg_match('/^(mail delivery (failed|subsystem)|delivery status notification|undeliverable|returned mail|failure notice)/i', trim($subject))) {
            return true;
        }

        return (bool) preg_match('/Content-Type:\s*multipart\/report;.*report-type=(delivery-status|feedback-report)/is', $rawBody)
            || self::isComplaint($subject, $rawBody);
    }

    /**
     * DSN "Status:" (RFC 3464): 5.X.X = fallo permanente (hard), 4.X.X =
     * temporal (soft). Sin ese campo, se asume NO permanente por precaución
     * — mejor no auto-suprimir una dirección válida por error que perder un
     * cliente. Solo si el MTA tampoco lo incluye, se cae a una heurística de
     * texto libre sobre frases típicas de rechazo permanente.
     */
    public static function isHardBounce(string $rawBody): bool
    {
        if (preg_match('/Status:\s*5\.\d+\.\d+/i', $rawBody)) {
            return true;
        }

        if (preg_match('/Status:\s*4\.\d+\.\d+/i', $rawBody)) {
            return false;
        }

        return (bool) preg_match(
            '/mailbox\s+(unavailable|not\s*found|does\s*not\s*exist)|user\s+unknown|no\s+such\s+user|address\s+rejected|550\s+5\.1\.1/i',
            $rawBody,
        );
    }

    public static function isComplaint(string $subject, string $rawBody): bool
    {
        return (bool) preg_match('/complaint|feedback\s*loop|marked\s+as\s+spam|abuse\s+report/i', $subject.' '.$rawBody);
    }

    /**
     * Destinatario fallido de los campos semi-estándar de un DSN.
     * "Final-Recipient:" es RFC 3464; "X-Failed-Recipients:" lo añaden
     * algunos MTAs (Postfix entre ellos) de forma no estándar pero habitual.
     */
    public static function findFailedRecipient(string $rawBody): ?string
    {
        if (preg_match('/Final-Recipient:\s*rfc822;\s*([^\s<>]+@[^\s<>]+)/i', $rawBody, $m)) {
            return strtolower(trim($m[1]));
        }

        if (preg_match('/X-Failed-Recipients:\s*([^\s<>]+@[^\s<>]+)/i', $rawBody, $m)) {
            return strtolower(trim($m[1]));
        }

        return null;
    }

    /**
     * Busca en el cuerpo crudo del bounce un Message-ID distinto al del
     * propio DSN (el del mensaje ORIGINAL, que el MTA suele reinsertar como
     * adjunto message/rfc822 o texto citado).
     */
    public static function findOriginalMessageId(string $rawBody, ?string $ownMessageId): ?string
    {
        if (! preg_match_all('/Message-ID:\s*<([^>\s]+)>/i', $rawBody, $matches)) {
            return null;
        }

        $own = self::normalizeMessageId((string) $ownMessageId);

        foreach ($matches[1] as $candidate) {
            $candidate = self::normalizeMessageId($candidate);

            if ($candidate !== '' && $candidate !== $own) {
                return $candidate;
            }
        }

        return null;
    }

    public static function normalizeMessageId(string $id): string
    {
        return trim($id, "<> \t\n\r\0\x0B");
    }
}
