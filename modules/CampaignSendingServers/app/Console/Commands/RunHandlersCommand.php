<?php

namespace Modules\CampaignSendingServers\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Models\CampaignTrackingLog;
use Modules\CampaignSendingServers\Library\ImapMailbox;
use Modules\CampaignSendingServers\Models\Blacklist;
use Modules\CampaignSendingServers\Models\BounceHandler;
use Modules\CampaignSendingServers\Models\BounceLog;
use Modules\CampaignSendingServers\Models\FeedbackLog;
use Modules\CampaignSendingServers\Models\FeedbackLoopHandler;

/**
 * Procesa los buzones IMAP/POP3 configurados como handlers de bounces y
 * feedback loops. Cada mensaje se parsea para extraer Message-Id y email
 * destinatario, se registra en bounce_logs / feedback_logs, y los hard
 * bounces / FBL se añaden a blacklist global.
 */
class RunHandlersCommand extends Command
{
    protected $signature = 'campaign-sending-servers:run-handlers
                            {--type=all : bounce|feedback|all}
                            {--limit=100 : Máx mensajes por handler en esta ejecución}';

    protected $description = 'Procesa los buzones IMAP/POP3 de bounces y feedback loops.';

    public function handle(): int
    {
        $type = $this->option('type');
        $limit = (int) $this->option('limit');

        if (in_array($type, ['bounce', 'all'], true)) {
            $this->processBounceHandlers($limit);
        }

        if (in_array($type, ['feedback', 'all'], true)) {
            $this->processFeedbackHandlers($limit);
        }

        return self::SUCCESS;
    }

    protected function processBounceHandlers(int $limit): void
    {
        $handlers = BounceHandler::query()->get();
        $this->info("Bounce handlers configurados: {$handlers->count()}");

        foreach ($handlers as $handler) {
            try {
                $box = $this->makeMailbox($handler);
                $box->open();
                $count = $box->eachUnseen(function (array $msg) use ($handler): void {
                    $this->processBounceMessage($msg, $handler);
                }, $limit);
                $box->close();
                $this->line("  ✓ {$handler->name}: {$count} mensajes procesados.");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$handler->name}: ".$e->getMessage());
            }
        }
    }

    protected function processFeedbackHandlers(int $limit): void
    {
        $handlers = FeedbackLoopHandler::query()->get();
        $this->info("Feedback handlers configurados: {$handlers->count()}");

        foreach ($handlers as $handler) {
            try {
                $box = $this->makeMailbox($handler);
                $box->open();
                $count = $box->eachUnseen(function (array $msg) use ($handler): void {
                    $this->processFeedbackMessage($msg, $handler);
                }, $limit);
                $box->close();
                $this->line("  ✓ {$handler->name}: {$count} mensajes procesados.");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$handler->name}: ".$e->getMessage());
            }
        }
    }

    protected function makeMailbox($handler): ImapMailbox
    {
        return new ImapMailbox(
            host: (string) $handler->host,
            port: (int) ($handler->port ?: 993),
            protocol: (string) ($handler->type ?: 'imap'),
            encryption: (string) ($handler->protocol ?: 'ssl'),
            username: (string) $handler->username,
            password: (string) $handler->password,
        );
    }

    /**
     * Parsea un bounce: extrae el email destinatario original y el
     * Message-Id de las cabeceras del DSN para ligar al campaign_tracking_log.
     */
    protected function processBounceMessage(array $msg, BounceHandler $handler): void
    {
        $email = $this->extractRecipientFromBounce($msg);
        $messageId = ImapMailbox::extractHeader($msg['header'], 'Message-Id') ?? null;
        $isHard = $this->detectHardBounce($msg['body']);

        BounceLog::create([
            'email' => $email,
            'bounce_type' => $isHard ? BounceLog::TYPE_HARD : BounceLog::TYPE_SOFT,
            'message_id' => $messageId,
            'description' => substr($msg['body'], 0, 1000),
            'bounce_handler_id' => $handler->id,
        ]);

        // Hard bounce → blacklist global
        if ($isHard && $email) {
            Blacklist::firstOrCreate(
                ['email' => $email],
                ['reason' => 'hard bounce', 'source' => Blacklist::SOURCE_BOUNCE],
            );

            // Actualiza tracking_log si existe
            if (class_exists(CampaignTrackingLog::class) && $messageId) {
                CampaignTrackingLog::where('message_id', $messageId)
                    ->update(['status' => 'bounced']);
            }
        }
    }

    /**
     * Parsea un feedback ARF.
     */
    protected function processFeedbackMessage(array $msg, FeedbackLoopHandler $handler): void
    {
        // ARF tiene un campo `Original-Mail-From:` o `Original-Rcpt-To:` en la 3ª parte MIME.
        $email = $this->extractEmailFromArf($msg['body']) ?? null;
        $messageId = ImapMailbox::extractHeader($msg['header'], 'Message-Id') ?? null;

        FeedbackLog::create([
            'email' => $email,
            'message_id' => $messageId,
            'description' => substr($msg['body'], 0, 1000),
            'feedback_loop_handler_id' => $handler->id,
        ]);

        // Reporte de spam → blacklist
        if ($email) {
            Blacklist::firstOrCreate(
                ['email' => $email],
                ['reason' => 'feedback loop / spam complaint', 'source' => Blacklist::SOURCE_FEEDBACK],
            );

            if (class_exists(CampaignTrackingLog::class) && $messageId) {
                CampaignTrackingLog::where('message_id', $messageId)
                    ->update(['status' => 'feedback']);
            }
        }
    }

    /**
     * Extrae el email destinatario original de un DSN buscando el header
     * `Final-Recipient:` o `Original-Recipient:` en el cuerpo del bounce.
     * Si no lo encuentra, intenta `To:` del mensaje original adjunto.
     */
    protected function extractRecipientFromBounce(array $msg): ?string
    {
        $body = $msg['body'];

        if (preg_match('/Final-Recipient:\s*[^;]+;\s*([^\s\r\n]+)/i', $body, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/Original-Recipient:\s*[^;]+;\s*([^\s\r\n]+)/i', $body, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/Failed Recipient:\s*([^\s\r\n]+)/i', $body, $m)) {
            return trim($m[1]);
        }
        // Fallback: To: del cuerpo embebido
        if (preg_match('/^To:\s*<?([^>\s]+)>?/im', $body, $m)) {
            $candidate = trim($m[1]);
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Heurística simple para detectar hard bounces (códigos SMTP 5.x.x).
     * La forma RFC-correcta es parsear el `Status:` del DSN.
     */
    protected function detectHardBounce(string $body): bool
    {
        if (preg_match('/^Status:\s*(5\.\d+\.\d+)/im', $body)) {
            return true;
        }
        if (preg_match('/\b5[0-9]{2}\b/', $body) && preg_match('/(no such user|user unknown|mailbox not found|address rejected)/i', $body)) {
            return true;
        }

        return false;
    }

    /**
     * Extrae el email de un ARF (Abuse Reporting Format) buscando
     * `Original-Mail-From:` o `Original-Rcpt-To:`.
     */
    protected function extractEmailFromArf(string $body): ?string
    {
        if (preg_match('/Original-Rcpt-To:\s*([^\s\r\n]+)/i', $body, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/Original-Mail-From:\s*<?([^>\s]+)>?/i', $body, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
