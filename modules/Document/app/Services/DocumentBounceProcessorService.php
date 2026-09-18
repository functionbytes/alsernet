<?php

namespace Modules\Document\Services;

use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

/**
 * Procesa los DSN (Delivery Status Notification / bounce) que llegan a la bandeja
 * configurada en documents.bounce_imap_* (normalmente la misma dirección desde la
 * que se envían los correos transaccionales de Document, ej. web@a-alvarez.com,
 * ya que ahí es donde el MTA reenvía los rebotes por defecto).
 *
 * No hay webhook de proveedor porque el correo sale por sendmail propio del
 * servidor (no Mailrelay/SES/Postmark) — los rebotes solo pueden detectarse
 * leyendo esa bandeja.
 *
 * Estrategia de correlación (dos pasos, por orden de confianza):
 *
 * 1) Message-ID: no se parsea el DSN según RFC 3464 completo (no hay librería de
 *    DSN instalada en el proyecto). En su lugar se busca, dentro del cuerpo crudo
 *    del bounce, el header "Message-ID: <...>" del mensaje ORIGINAL que el MTA
 *    suele reinsertar (como parte adjunta message/rfc822 o como texto citado). Se
 *    descarta el primer Message-ID que coincide con el del propio DSN y se usa el
 *    primero de los siguientes. Correlación exacta, se usa siempre que esté.
 *
 * 2) Destinatario (fallback, solo si (1) no encontró nada): algunos MTAs de
 *    destino no reinsertan las cabeceras originales en el DSN. Se extrae el
 *    destinatario fallido de los campos "Final-Recipient:"/"X-Failed-Recipients:"
 *    (semi-estándar en DSN) y se busca el EmailLog 'sent' más reciente a ese
 *    destinatario dentro de una ventana de 7 días. SOLO se marca si hay
 *    exactamente UN candidato — con más de uno, es ambiguo (qué envío rebotó
 *    exactamente) y se deja sin correlacionar antes que arriesgar un falso
 *    positivo (mismo criterio que LogEmailSent::findQueued para el caso
 *    queued→sent). Se anota en el motivo que fue una correlación heurística, no
 *    exacta, para que quede trazable en el log.
 */
class DocumentBounceProcessorService
{
    /**
     * @return array{connected: bool, processed: int, matched: int, unmatched: int}
     */
    public function process(int $limit = 50): array
    {
        if (! class_exists(ClientManager::class)) {
            Log::error('DocumentBounceProcessorService: webklex/php-imap no está instalado.');

            return ['connected' => false, 'processed' => 0, 'matched' => 0, 'unmatched' => 0];
        }

        $host = Setting::get('documents.bounce_imap_host');

        if (blank($host)) {
            return ['connected' => false, 'processed' => 0, 'matched' => 0, 'unmatched' => 0];
        }

        $cm = new ClientManager;

        $client = $cm->make([
            'host' => $host,
            'port' => (int) Setting::get('documents.bounce_imap_port', 993),
            'encryption' => Setting::get('documents.bounce_imap_encryption', 'ssl') ?: false,
            'validate_cert' => true,
            'username' => Setting::get('documents.bounce_imap_username'),
            'password' => Setting::get('documents.bounce_imap_password'),
            'protocol' => 'imap',
        ]);

        $client->connect();

        $folder = $client->getFolder(Setting::get('documents.bounce_imap_folder', 'INBOX'));
        $messages = $folder->query()->unseen()->limit($limit)->get();

        $processed = 0;
        $matched = 0;
        $unmatched = 0;

        foreach ($messages as $message) {
            $processed++;

            try {
                if ($this->processMessage($message)) {
                    $matched++;
                } else {
                    $unmatched++;
                }
            } catch (\Throwable $e) {
                Log::warning('DocumentBounceProcessorService: fallo procesando un mensaje', [
                    'error' => $e->getMessage(),
                ]);
                $unmatched++;
            }

            // Se marca "Seen" siempre, incluso sin match, para no reprocesar el
            // mismo bounce en cada ejecución del comando.
            $message->setFlag('Seen');
        }

        $client->disconnect();

        return ['connected' => true, 'processed' => $processed, 'matched' => $matched, 'unmatched' => $unmatched];
    }

    /**
     * @return bool true si el bounce se correlacionó con un EmailLog y se marcó
     */
    private function processMessage(Message $message): bool
    {
        $ownMessageId = $this->normalizeMessageId((string) ($message->getMessageId()?->first() ?? ''));
        $rawBody = $message->getRawBody();
        $subject = (string) ($message->getSubject()?->first() ?? 'Bounce recibido');

        $originalMessageId = $this->findOriginalMessageId($rawBody, $ownMessageId);

        if ($originalMessageId) {
            $emailLog = EmailLog::where('message_id', $originalMessageId)->first();

            if ($emailLog) {
                $this->markBounced($emailLog, $subject);

                return true;
            }
        }

        // Fallback: sin Message-ID original, intentar por destinatario.
        return $this->processMessageByRecipient($rawBody, $subject);
    }

    /**
     * Fallback cuando no hay Message-ID original en el DSN: correlaciona por
     * destinatario fallido, solo si es inequívoco (ver docblock de la clase).
     */
    private function processMessageByRecipient(string $rawBody, string $subject): bool
    {
        $recipient = $this->findFailedRecipient($rawBody);

        if (! $recipient) {
            return false;
        }

        $candidates = EmailLog::where('module', 'Document')
            ->sent()
            ->whereJsonContains('to_addresses', $recipient)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($candidates->count() !== 1) {
            return false;
        }

        $this->markBounced($candidates->first(), $subject, heuristic: true);

        return true;
    }

    private function markBounced(EmailLog $emailLog, string $reason, bool $heuristic = false): void
    {
        // No degradar un estado ya terminal más específico si por lo que sea el
        // mismo bounce llega dos veces (aunque ya se marca Seen para evitarlo).
        if (in_array($emailLog->status, [EmailStatus::Bounced, EmailStatus::Complained], true)) {
            return;
        }

        if ($heuristic) {
            $reason = '[correlación por destinatario, sin Message-ID en el DSN] '.$reason;
        }

        $emailLog->markAsBounced($reason);
    }

    /**
     * Extrae el destinatario fallido de los campos semi-estándar de un DSN.
     * "Final-Recipient:" es RFC 3464; "X-Failed-Recipients:" lo añaden algunos
     * MTAs (Postfix entre ellos) de forma no estándar pero habitual.
     */
    private function findFailedRecipient(string $rawBody): ?string
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
     * Busca en el cuerpo crudo del bounce un Message-ID distinto al del propio DSN.
     */
    private function findOriginalMessageId(string $rawBody, string $ownMessageId): ?string
    {
        if (! preg_match_all('/Message-ID:\s*<([^>\s]+)>/i', $rawBody, $matches)) {
            return null;
        }

        foreach ($matches[1] as $candidate) {
            $candidate = $this->normalizeMessageId($candidate);

            if ($candidate !== '' && $candidate !== $ownMessageId) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeMessageId(string $id): string
    {
        return trim($id, "<> \t\n\r\0\x0B");
    }
}
