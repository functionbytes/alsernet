<?php

namespace Modules\Mailrelay\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateBulkEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The array of emails to validate
     */
    public array $emails;

    /**
     * The import job ID
     */
    public string $importJobId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(array $emails, string $importJobId)
    {
        $this->emails = $emails;
        $this->importJobId = $importJobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Dispatch individual validation jobs for each email
        foreach ($this->emails as $email) {
            ValidateEmailJob::dispatch($email, $this->importJobId);
        }
    }
}
