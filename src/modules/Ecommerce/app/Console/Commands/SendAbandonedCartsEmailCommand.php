<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Models\Setting;
use Modules\Ecommerce\Jobs\SendAbandonedCartEmailJob;
use Modules\Ecommerce\Models\Cart;

class SendAbandonedCartsEmailCommand extends Command
{
    protected $signature = 'ecommerce:send-abandoned-carts';

    protected $description = 'Send abandoned cart recovery emails to eligible customers';

    public function handle(): void
    {
        if (Setting::get('ecommerce.abandoned_cart_enabled') !== '1') {
            $this->info('Abandoned cart emails are disabled. Skipping.');

            return;
        }

        $delayHours = (int) (Setting::get('ecommerce.abandoned_cart_delay_hours') ?: 1);
        $maxHours = (int) (Setting::get('ecommerce.abandoned_cart_max_hours') ?: 168);
        $batchLimit = (int) (Setting::get('ecommerce.abandoned_cart_email_limit') ?: 50);
        $excludedSlugs = array_filter(
            array_map('trim', explode(',', Setting::get('ecommerce.abandoned_cart_exclude_categories') ?: ''))
        );

        $query = Cart::query()
            ->select('customer_id')
            ->whereNotNull('customer_id')
            ->whereBetween('updated_at', [
                now()->subHours($maxHours),
                now()->subHours($delayHours),
            ])
            ->groupBy('customer_id');

        if (! empty($excludedSlugs)) {
            $query->whereHas('product.categories', function ($q) use ($excludedSlugs) {
                $q->whereNotIn('slug', $excludedSlugs);
            });
        }

        $customerIds = $query->pluck('customer_id')->unique()->take($batchLimit);

        foreach ($customerIds as $customerId) {
            dispatch(new SendAbandonedCartEmailJob($customerId));
        }

        $this->info("Dispatched {$customerIds->count()} abandoned cart emails.");
    }
}
