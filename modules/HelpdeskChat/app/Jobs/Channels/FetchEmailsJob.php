<?php

namespace Modules\HelpdeskChat\Jobs\Channels;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Services\Channels\Email\ImapClient;

class FetchEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Email $email
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! $this->email->imap_enabled) {
            Log::debug('IMAP not enabled for email channel', [
                'email_id' => $this->email->id,
            ]);

            return;
        }

        try {
            $imapClient = new ImapClient($this->email);

            if (! $imapClient->connect()) {
                Log::error('Failed to connect to IMAP server', [
                    'email_id' => $this->email->id,
                    'email' => $this->email->email,
                ]);

                return;
            }

            // Fetch unread emails (limit to 20 at a time to avoid overwhelming the system)
            $emails = $imapClient->fetchUnreadEmails(20);

            Log::info('Fetched emails from IMAP', [
                'email_id' => $this->email->id,
                'count' => count($emails),
            ]);

            // Process each email
            foreach ($emails as $emailData) {
                // Dispatch job to process this email
                ProcessInboundEmailJob::dispatch($this->email, $emailData);

                // Mark as read
                $imapClient->markAsRead($emailData['uid']);
            }

            $imapClient->disconnect();
        } catch (\Exception $e) {
            Log::error('Error fetching emails', [
                'email_id' => $this->email->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FetchEmailsJob failed', [
            'email_id' => $this->email->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
