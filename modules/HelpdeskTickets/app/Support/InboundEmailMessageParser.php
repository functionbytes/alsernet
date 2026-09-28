<?php

namespace Modules\HelpdeskTickets\Support;

use Webklex\PHPIMAP\Attribute as ImapAttribute;
use Webklex\PHPIMAP\Message as ImapMessage;

/**
 * Extracción de datos "puros" (sin BD, sin IMAP en vivo) de un mensaje
 * webklex/php-imap: cabeceras, direcciones, prioridad detectada e
 * identificadores. Extraído de FetchTicketEmailsJob (28-sep-2026, job de
 * 1103 líneas) — el job la llama directamente; ver el docblock de
 * FetchTicketEmailsJob para el porqué del reparto.
 */
class InboundEmailMessageParser
{
    /**
     * Convierte un Attribute de webklex de un header simple (no de dirección,
     * p. ej. Message-ID/Subject/Date) a string plano, o null si no hay valor.
     * Attribute::__toString() ya hace implode(", ", $values) — seguro para
     * estos headers porque sus valores son escalares, a diferencia de
     * from/to/cc/bcc (ver formatAddressAttribute()).
     */
    public function stringAttribute(?ImapAttribute $attribute): ?string
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
    public function decodeMimeHeader(string $value): string
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
    public function formatAddressAttribute(?ImapAttribute $attribute): ?string
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
     * @return array<int, string>
     */
    public function splitReferences(?string $references): array
    {
        if (! $references) {
            return [];
        }

        // RFC 5322 suele separar References con espacios, aunque algunos
        // servidores/clientes los entregan separados por comas. Aceptar solo
        // comas rompía el hilado cuando el correo no traía In-Reply-To y
        // References venía en su formato habitual: <id1> <id2>.
        preg_match_all('/<([^<>]+)>|([^\s,<>]+)/', $references, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        return array_values(array_filter(array_map(
            static fn (array $match): string => trim((string) ($match[1] ?? $match[2] ?? '')),
            $matches,
        ), static fn (string $id): bool => $id !== ''));
    }

    /**
     * Extract email address from name+email format.
     */
    public function extractEmailAddress(string $from): string
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
    public function extractEmailName(string $from): ?string
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
    public function extractHeaders(ImapMessage $message): array
    {
        $headers = [
            'Message-ID' => $this->stringAttribute($message->message_id),
            'In-Reply-To' => $this->stringAttribute($message->in_reply_to),
            'References' => $this->stringAttribute($message->references),
            'Subject' => $this->stringAttribute($message->subject),
            'Date' => $this->stringAttribute($message->date),
        ];

        // Veredicto antispam que ya calculó el servidor de correo entrante
        // (SpamAssassin, Rspamd y similares lo escriben en estas cabeceras).
        // Se archiva tal cual llega: el chip del detalle enseña la puntuación
        // REAL del filtro, no una inventada por nosotros. Los correos que no
        // pasen por un filtro simplemente no traerán ninguna de las cuatro.
        // Header::get() normaliza guiones y mayúsculas internamente.
        $header = $message->getHeader();
        // List-*, Precedence y Auto-Submitted: marcan boletines, listas y
        // respuestas automáticas (RFC 2369, 2919, 3834). Los usa
        // SpamClassifierService::quarantineIfBulk().
        foreach (['X-Spam-Score', 'X-Spam-Status', 'X-Spam-Level', 'X-Spam-Flag', 'List-Unsubscribe', 'List-Id', 'Precedence', 'Auto-Submitted'] as $name) {
            $value = $header?->get($name);
            $value = $value === null ? null : trim((string) $value);
            if ($value !== null && $value !== '') {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Fuente cruda del correo (headers + cuerpo), igual al patrón interno de
     * Message::save() en webklex/php-imap.
     */
    public function rawSource(ImapMessage $message): ?string
    {
        $raw = ($message->getHeader()?->raw ?? '')."\r\n\r\n".$message->getRawBody();

        return trim($raw) !== '' ? $raw : null;
    }

    /**
     * Detect priority from subject keywords.
     */
    public function detectPriority(string $subject): string
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
    public function generateMessageId(): string
    {
        return uniqid().'@'.config('app.name');
    }

    /**
     * Genera una identidad determinista para un mensaje IMAP sin
     * Message-ID. El UID solo es único dentro de una carpeta, por eso el
     * namespace incluye id/host/usuario/carpeta del canal. Si el doble de
     * pruebas o un proveedor IMAP no expone UID, se conserva el fallback
     * aleatorio de generateMessageId() y no se inventa una deduplicación
     * basada en asunto/cuerpo (podría borrar dos correos legítimos iguales).
     */
    public function stableImapMessageId(ImapMessage $message, array $connection = []): ?string
    {
        try {
            $uid = $message->uid;
        } catch (\Throwable) {
            return null;
        }

        if (! is_int($uid) && ! (is_string($uid) && ctype_digit($uid))) {
            return null;
        }

        $config = $connection !== [] ? $connection : (array) config('helpdesk.email.imap', []);
        $scope = implode('|', [
            $config['id'] ?? '',
            $config['server'] ?? $config['host'] ?? '',
            $config['username'] ?? '',
            $config['folder'] ?? 'INBOX',
        ]);

        return 'imap-'.hash('sha256', $scope.'|'.$uid).'@'.config('app.name');
    }
}
