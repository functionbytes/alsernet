<?php

namespace Modules\Chat\Jobs\Customers;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Customers\Customer;

class BulkDeleteCustomers implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $accountId,
        public array $customerIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $deletedCount = Customer::whereIn('id', $this->customerIds)
            ->where('account_id', $this->accountId)
            ->delete();

        Log::info("Bulk delete completed: {$deletedCount} customers deleted");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'account_id' => $this->accountId,
            'customer_count' => count($this->customerIds),
            'error' => $exception->getMessage(),
        ]);
    }
}
