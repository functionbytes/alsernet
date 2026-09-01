<?php

namespace Modules\HelpdeskEmailLog\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskEmailLog\Support\DsnMessageParser;
use Throwable;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

/**
 * Procesa los DSN (Delivery Status Notification / bounce) y quejas de spam
 * que llegan a los buzones configurados en BounceMailboxesRepository —
 * generalización de lo que antes vivía como
 * Modules\Document\Services\DocumentBounceProcessorService, acotado a un
 * único buzón fijo del módulo Document. No hay webhook de proveedor porque
 * el correo real del proyecto sale por SMTP genérico (no Mailrelay/SES/
 * Postmark, ver Modules\HelpdeskEmailLog\Http\Controllers\... si algún día
 * se conecta uno) — los rebotes solo se pueden detectar leyendo estas
 * bandejas.
 *
 * Estrategia de correlación (dos pasos, por orden de confianza), delegada a
 * EmailBounceCorrelatorService para poder compartirla con un futuro
 * receptor de webhooks:
 *
 * 1) Message-ID: no se parsea el DSN según RFC 3464 completo (no hay
 *    librería de parseo DSN instalada en el proyecto). Se busca en el
 *    cuerpo crudo el header "Message-ID: <...>" del mensaje ORIGINAL que el
 *    MTA suele reinsertar (adjunto message/rfc822 o texto citado),
 *    descartando el propio Message-ID del DSN. Correlación exacta.
 * 2) Destinatario (fallback): "Final-Recipient:"/"X-Failed-Recipients:",
 *    solo si hay exactamente un candidato ambiguo-libre — ver
 *    EmailBounceCorrelatorService::correlateByRecipient().
 */
class BounceProcessorService
{
    public function __construct(
        private readonly BounceMailboxesRepository $mailboxes,
        private readonly EmailBounceCorrelatorService $correlator,
    ) {}

    /**
     * @return array{connected: bool, mailboxes: int, processed: int, matched: int, unmatched: int}
     */
    public function process(int $limitPerMailbox = 50): array
    {
        if (! class_exists(ClientManager::class)) {
            Log::error('HelpdeskEmailLog BounceProcessorService: webklex/php-imap no está instalado.');

            return ['connected' => false, 'mailboxes' => 0, 'processed' => 0, 'matched' => 0, 'unmatched' => 0];
        }

        $mailboxes = $this->mailboxes->enabled();

        $totals = [
            'connected' => false,
            'mailboxes' => count($mailboxes),
            'processed' => 0,
            'matched' => 0,
            'unmatched' => 0,
        ];

        foreach ($mailboxes as $mailbox) {
            $result = $this->processMailbox($mailbox, $limitPerMailbox);

            if ($result['connected']) {
                $totals['connected'] = true;
            }

            $totals['processed'] += $result['processed'];
            $totals['matched'] += $result['matched'];
            $totals['unmatched'] += $result['unmatched'];

            $this->mailboxes->recordHealth(
                (string) $mailbox['id'],
                success: $result['connected'],
                error: $result['error'] ?? null,
            );
        }

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $mailbox
     * @return array{connected: bool, processed: int, matched: int, unmatched: int, error?: string}
     */
    private function processMailbox(array $mailbox, int $limit): array
    {
        $host = (string) ($mailbox['host'] ?? '');

        if ($host === '') {
            return ['connected' => false, 'processed' => 0, 'matched' => 0, 'unmatched' => 0, 'error' => 'Sin host configurado'];
        }

        $options = [
            'host' => $host,
            'port' => (int) ($mailbox['port'] ?? 993),
            'encryption' => ($mailbox['encryption'] ?? 'ssl') ?: false,
            'validate_cert' => true,
            'username' => (string) ($mailbox['username'] ?? ''),
            'password' => (string) ($mailbox['password'] ?? ''),
            'protocol' => 'imap',
        ];

        // Mismo ajuste que FetchTicketEmailsJob/TicketChannelMailerService
        // para servidores con TLS obsoleto (ver helpdesk.imap.legacy_tls_hosts)
        // — DocumentBounceProcessorService original NO lo aplicaba, así que si
        // el buzón de rebotes de Document estaba en un host de esa lista,
        // llevaba tiempo roto en silencio.
        if ($this->needsLegacyTls($host)) {
            $options['ssl_options'] = ['ciphers' => 'DEFAULT@SECLEVEL=0'];
        }

        try {
            $client = (new ClientManager)->make($options);
            $client->connect();
        } catch (Throwable $e) {
            Log::warning('HelpdeskEmailLog BounceProcessorService: fallo de conexión IMAP', [
                'mailbox' => $mailbox['label'] ?? $host,
                'error' => $e->getMessage(),
            ]);

            return ['connected' => false, 'processed' => 0, 'matched' => 0, 'unmatched' => 0, 'error' => $e->getMessage()];
        }

        $processed = 0;
        $matched = 0;
        $unmatched = 0;

        try {
            $folder = $client->getFolder($mailbox['folder'] ?? 'INBOX');
            $messages = $folder->query()->unseen()->limit($limit)->get();

            $moduleScope = $mailbox['module_scope'] ?? null;
            $moduleScope = (is_array($moduleScope) && $moduleScope !== []) ? $moduleScope : null;

            foreach ($messages as $message) {
                $processed++;

                try {
                    if ($this->processMessage($message, $moduleScope)) {
                        $matched++;
                    } else {
                        $unmatched++;
                    }
                } catch (Throwable $e) {
                    Log::warning('HelpdeskEmailLog BounceProcessorService: fallo procesando un mensaje', [
                        'error' => $e->getMessage(),
                    ]);
                    $unmatched++;
                }

                // Se marca "Seen" siempre, incluso sin match, para no
                // reprocesar el mismo bounce en cada corrida.
                $message->setFlag('Seen');
            }
        } finally {
            $client->disconnect();
        }

        return ['connected' => true, 'processed' => $processed, 'matched' => $matched, 'unmatched' => $unmatched];
    }

    /**
     * @param  list<string>|null  $moduleScope
     */
    private function processMessage(Message $message, ?array $moduleScope): bool
    {
        $ownMessageId = (string) ($message->getMessageId()?->first() ?? '');
        $rawBody = $message->getRawBody();
        $subject = (string) ($message->getSubject()?->first() ?? 'Bounce recibido');

        $isComplaint = DsnMessageParser::isComplaint($subject, $rawBody);
        $isHard = DsnMessageParser::isHardBounce($rawBody);

        $originalMessageId = DsnMessageParser::findOriginalMessageId($rawBody, $ownMessageId);

        if ($originalMessageId && $this->correlator->correlateByMessageId($originalMessageId, $subject, $isHard, $isComplaint)) {
            return true;
        }

        $recipient = DsnMessageParser::findFailedRecipient($rawBody);

        if (! $recipient) {
            return false;
        }

        return $this->correlator->correlateByRecipient($recipient, $subject, $moduleScope, $isHard, $isComplaint);
    }

    /**
     * Si el host está en la lista de servidores con TLS obsoleto (ver
     * modules/Helpdesk/config/helpdesk.php: imap.legacy_tls_hosts).
     */
    private function needsLegacyTls(string $host): bool
    {
        $hosts = (array) config('helpdesk.imap.legacy_tls_hosts', []);

        return in_array(mb_strtolower($host), array_map('mb_strtolower', $hosts), true);
    }
}
