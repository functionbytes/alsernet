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
 * Estrategia de correlación: no se parsea el DSN según RFC 3464 completo (no hay
 * librería de DSN instalada en el proyecto). En su lugar se busca, dentro del
 * cuerpo crudo del bounce, el header "Message-ID: <...>" del mensaje ORIGINAL que
 * el MTA suele reinsertar (como parte adjunta message/rfc822 o como texto citado).
 * Se descarta el primer Message-ID que coincide con el del propio DSN (para no
 * confundirlo con el mensaje original) y se usa el primero de los siguientes.
 * Si no aparece ningún Message-ID ajeno al del DSN, el mensaje se cuenta como
 * "sin correlacionar" — no se intenta adivinar por destinatario para evitar falsos
 * positivos (marcar como rebotado un envío que no lo fue).
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

        $originalMessageId = $this->findOriginalMessageId($rawBody, $ownMessageId);

        if (! $originalMessageId) {
            return false;
        }

        $emailLog = EmailLog::where('message_id', $originalMessageId)->first();

        if (! $emailLog) {
            return false;
        }

        // No degradar un estado ya terminal más específico si por lo que sea el
        // mismo bounce llega dos veces (aunque ya se marca Seen para evitarlo).
        if (in_array($emailLog->status, [EmailStatus::Bounced, EmailStatus::Complained], true)) {
            return true;
        }

        $reason = (string) ($message->getSubject()?->first() ?? 'Bounce recibido');
        $emailLog->markAsBounced($reason);

        return true;
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
