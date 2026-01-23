<?php

namespace Modules\HelpdeskChat\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskChat\Jobs\Channels\FetchEmailsJob;
use Modules\HelpdeskChat\Models\Channels\Email;

class FetchEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channels:fetch-emails {--email_id= : Specific email channel ID to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch emails from IMAP for all email channels';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $emailId = $this->option('email_id');

        if ($emailId) {
            // Fetch for specific email channel
            $email = Email::find($emailId);

            if (! $email) {
                $this->error("Email channel with ID {$emailId} not found.");

                return 1;
            }

            if (! $email->imap_enabled) {
                $this->warn("IMAP is not enabled for email channel {$email->email}");

                return 1;
            }

            $this->info("Fetching emails for: {$email->email}");
            FetchEmailsJob::dispatch($email);
            $this->info('✓ Fetch job dispatched');

            return 0;
        }

        // Fetch for all email channels with IMAP enabled
        $emailChannels = Email::where('imap_enabled', true)->get();

        if ($emailChannels->isEmpty()) {
            $this->info('No email channels with IMAP enabled found.');

            return 0;
        }

        $this->info("Found {$emailChannels->count()} email channel(s) with IMAP enabled");

        foreach ($emailChannels as $email) {
            $this->info("Dispatching fetch job for: {$email->email}");
            FetchEmailsJob::dispatch($email);
        }

        $this->info('✓ All fetch jobs dispatched');

        return 0;
    }
}
