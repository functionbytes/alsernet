<?php

namespace Modules\Remarketing\Observers;

use Illuminate\Support\Facades\DB;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\Cart;
use Modules\Remarketing\Models\Order;
use Modules\Remarketing\Services\AutomationService;

class OrderObserver
{
    public function __construct(
        protected AutomationService $automations
    ) {}

    public function created(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $customer = $order->customer;

            if (! $customer) {
                return;
            }

            $isFirstOrder = ((int) $customer->orders_count) === 0;

            $customer->forceFill([
                'orders_count' => $customer->orders_count + 1,
                'last_order_at' => $order->placed_at,
                'clv_historical' => (float) $customer->clv_historical + (float) $order->total,
            ])->save();

            $cart = Cart::where('store_id', $order->store_id)
                ->where('email', $customer->email)
                ->whereIn('status', ['active', 'abandoned'])
                ->latest('updated_at')
                ->first();

            if ($cart) {
                $cart->forceFill([
                    'status' => 'recovered',
                    'recovered_at' => now(),
                ])->save();
            }

            $context = [
                'order_id' => $order->id,
                'is_first_order' => $isFirstOrder,
            ];

            Automation::where('store_id', $order->store_id)
                ->where('trigger', 'post_purchase')
                ->where('status', 'active')
                ->each(fn (Automation $a) => $this->automations->trigger($a, $customer, $context));
        });
    }
}
