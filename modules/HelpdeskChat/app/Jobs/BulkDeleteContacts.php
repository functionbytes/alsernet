<?php

namespace Modules\HelpdeskChat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Models\Contacts\Contact;

class BulkDeleteContacts implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $accountId,
        public array $contactIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $deletedCount = Contact::whereIn('id', $this->contactIds)
            ->where('account_id', $this->accountId)
            ->delete();

        Log::info("Bulk delete completed: {$deletedCount} contacts deleted");
    }
}
