<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Modules\Ecommerce\Jobs\SendAbandonedCartEmailJob;
use Modules\Ecommerce\Models\Cart;

class SendAbandonedCartsEmailCommand extends Command
{
    protected $signature = 'ecommerce:send-abandoned-carts';

    protected $description = 'Send abandoned cart emails';

    public function handle(): void
    {
        $customers = Cart::query()
            ->whereNotNull('customer_id')
            ->where('updated_at', '<', now()->subHours(24))
            ->where('updated_at', '>', now()->subHours(48))
            ->pluck('customer_id')
            ->unique();

        foreach ($customers as $customerId) {
            dispatch(new SendAbandonedCartEmailJob($customerId));
        }

        $this->info("Dispatched {$customers->count()} abandoned cart emails.");
    }
}
