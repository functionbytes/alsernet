<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Jobs\ProcessEmailInboundJob;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

class FetchEmailTicketsCommand extends Command
{
    protected $signature = 'helpdesk:fetch-emails
                            {--limit= : Maximum number of emails to process (default: batch_size from config)}
                            {--dry-run : Parse emails without creating conversations}';

    protected $description = 'Fetch unseen emails via IMAP and convert them to helpdesk conversations';

    public function handle(): int
    {
        if (! config('helpdesk.imap.enabled')) {
            $this->warn('IMAP is disabled. Set HELPDESK_IMAP_ENABLED=true to enable.');

            return self::SUCCESS;
        }

        if (! class_exists(ClientManager::class)) {
            $this->error('webklex/php-imap is not installed. Run: composer require webklex/php-imap');

            return self::FAILURE;
        }

        $host = config('helpdesk.imap.server');

        if (blank($host)) {
            $this->error('IMAP server not configured. Set HELPDESK_IMAP_SERVER in .env');

            return self::FAILURE;
        }

        $limit = (int) ($this->option('limit') ?? config('helpdesk.imap.batch_size', 50));
        $dryRun = (bool) $this->option('dry-run');

        try {
            $processed = $this->fetchAndProcess($limit, $dryRun);
        } catch (\Throwable $e) {
            Log::error('helpdesk:fetch-emails failed', ['error' => $e->getMessage()]);
            $this->error("IMAP fetch failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $label = $dryRun ? 'would process' : 'dispatched';
        $this->info("Done — {$processed} email(s) {$label}.");

        return self::SUCCESS;
    }

    private function fetchAndProcess(int $limit, bool $dryRun): int
    {
        $cm = new ClientManager;

        $client = $cm->make([
            'host' => config('helpdesk.imap.server'),
            'port' => config('helpdesk.imap.port', 993),
            'encryption' => config('helpdesk.imap.encryption', 'ssl'),
            'validate_cert' => true,
            'username' => config('helpdesk.imap.username'),
            'password' => config('helpdesk.imap.password'),
            'protocol' => 'imap',
        ]);

        $client->connect();

        $folder = $client->getFolder(config('helpdesk.imap.folder', 'INBOX'));
        $messages = $folder->query()->unseen()->limit($limit)->get();

        $processed = 0;

        foreach ($messages as $message) {
            $parsed = $this->parseMessage($message);

            if (blank($parsed['from'])) {
                $message->setFlag('Seen');

                continue;
            }

            if (! $dryRun) {
                ProcessEmailInboundJob::dispatch($parsed);
                $message->setFlag('Seen');
            } else {
                $this->line("Would process: [{$parsed['from']}] {$parsed['subject']}");
            }

            $processed++;
        }

        $client->disconnect();

        return $processed;
    }

    /**
     * @return array{from: string, from_name: ?string, subject: string, body: string, message_id: ?string}
     */
    private function parseMessage(Message $message): array
    {
        $fromAddresses = $message->getFrom();
        $firstFrom = $fromAddresses?->first();

        return [
            'from' => $firstFrom?->mail ?? '',
            'from_name' => $firstFrom?->personal ?? null,
            'subject' => (string) ($message->getSubject()?->first() ?? ''),
            'body' => (string) ($message->getTextBody() ?? $message->getHTMLBody() ?? ''),
            'message_id' => (string) ($message->getMessageId()?->first() ?? null),
        ];
    }
}
