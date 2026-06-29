<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Events\OrderCreated;
use Modules\Ecommerce\Models\Affiliate;
use Modules\Ecommerce\Models\AffiliateReferral;

class RegisterAffiliateReferral
{
    public function handle(OrderCreated $event): void
    {
        $affiliateId = (int) request()->cookie('affiliate_ref');
        if (! $affiliateId) {
            return;
        }

        $affiliate = Affiliate::query()
            ->where('id', $affiliateId)
            ->where('status', 'active')
            ->first();

        if (! $affiliate) {
            return;
        }

        $order = $event->order;

        if ($order->customer_id === $affiliate->customer_id) {
            return;
        }

        $alreadyExists = AffiliateReferral::query()->where('order_id', $order->id)->exists();
        if ($alreadyExists) {
            return;
        }

        $commissionAmount = round((float) $order->total * ((float) $affiliate->commission_rate / 100), 2);

        try {
            AffiliateReferral::query()->create([
                'affiliate_id' => $affiliate->id,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'order_total' => $order->total,
                'commission_amount' => $commissionAmount,
                'status' => 'pending',
            ]);

            $affiliate->increment('total_earned', $commissionAmount);
        } catch (\Throwable $e) {
            Log::warning('RegisterAffiliateReferral failed', [
                'order_id' => $order->id,
                'affiliate_id' => $affiliate->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
