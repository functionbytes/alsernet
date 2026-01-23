<?php

namespace Modules\HelpdeskChat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Label;

class BulkAddLabelToContacts implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $accountId,
        public array $contactIds,
        public array $labelIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $labels = Label::whereIn('id', $this->labelIds)
            ->where('account_id', $this->accountId)
            ->pluck('title')
            ->toArray();

        $processedCount = 0;

        foreach ($this->contactIds as $contactId) {
            $contact = Contact::where('id', $contactId)
                ->where('account_id', $this->accountId)
                ->first();

            if ($contact) {
                $contact->addLabels($labels);
                $processedCount++;
            }
        }

        Log::info("Bulk add labels completed: {$processedCount} contacts processed");
    }
}
