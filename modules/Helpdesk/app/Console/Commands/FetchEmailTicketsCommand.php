<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Jobs\Helpdesks\FetchTicketEmailsJob;

class FetchEmailTicketsCommand extends Command
{
    protected $signature = 'imap:emailticket';

    protected $description = 'Fetch emails from IMAP inbox and create tickets';

    public function handle(): int
    {
        try {
            FetchTicketEmailsJob::dispatch();

            $this->info('FetchTicketEmailsJob dispatched successfully.');
            Log::info('imap:emailticket: FetchTicketEmailsJob dispatched.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('imap:emailticket command failed', ['error' => $e->getMessage()]);
            $this->error('Command failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
