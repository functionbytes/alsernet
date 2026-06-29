<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Modules\Ecommerce\Models\CustomerDeletionRequest;

class CancelExpiredDeletionRequests extends Command
{
    protected $signature = 'ecommerce:cancel-expired-deletion-requests';

    protected $description = 'Cancel expired customer deletion requests';

    public function handle(): void
    {
        $count = CustomerDeletionRequest::query()
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subDays(7))
            ->update(['status' => 'cancelled']);

        $this->info("Cancelled {$count} expired deletion requests.");
    }
}
