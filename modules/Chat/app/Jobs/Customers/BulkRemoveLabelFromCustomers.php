<?php

namespace Modules\Chat\Jobs\Customers;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Conversations\ConversationLabel;
use Modules\Chat\Models\Customers\Customer;

class BulkRemoveLabelFromCustomers implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $accountId,
        public array $customerIds,
        public array $labelIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $labels = ConversationLabel::whereIn('id', $this->labelIds)
            ->where('account_id', $this->accountId)
            ->pluck('name')
            ->toArray();

        $customers = Customer::whereIn('id', $this->customerIds)
            ->where('account_id', $this->accountId)
            ->get();

        foreach ($customers as $customer) {
            $customer->removeLabels($labels);
        }

        Log::info("Bulk remove labels completed: {$customers->count()} customers processed");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'account_id' => $this->accountId,
            'customer_count' => count($this->customerIds),
            'label_ids' => $this->labelIds,
            'error' => $exception->getMessage(),
        ]);
    }
}
