<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\AutomationRun;
use Modules\Remarketing\Models\Cart;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Order;
use Modules\Remarketing\Services\AutomationService;

class MarkAbandonedCartsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct()
    {
        $this->onQueue('remarketing');
    }

    public function handle(AutomationService $automationService): void
    {
        $carts = Cart::query()
            ->where('status', 'active')
            ->where('updated_at', '<', now()->subMinutes(60))
            ->whereNotNull('email')
            ->cursor();

        foreach ($carts as $cart) {
            $hasConvertedOrder = $this->hasAssociatedOrder($cart);

            if ($hasConvertedOrder) {
                continue;
            }

            $cart->update([
                'status' => 'abandoned',
                'abandoned_at' => now(),
            ]);

            $this->triggerCartAbandonedAutomations($cart, $automationService);
        }
    }

    private function hasAssociatedOrder(Cart $cart): bool
    {
        if (! $cart->email) {
            return false;
        }

        return Order::query()
            ->where('store_id', $cart->store_id)
            ->whereHas('customer', fn ($q) => $q->where('email', strtolower($cart->email)))
            ->where('placed_at', '>=', $cart->created_at)
            ->exists();
    }

    private function triggerCartAbandonedAutomations(Cart $cart, AutomationService $automationService): void
    {
        $customer = $cart->customer_id
            ? Customer::find($cart->customer_id)
            : Customer::where('store_id', $cart->store_id)
                ->where('email', strtolower($cart->email))
                ->first();

        if (! $customer) {
            return;
        }

        $automations = Automation::query()
            ->where('store_id', $cart->store_id)
            ->where('trigger', 'cart_abandoned')
            ->where('status', 'active')
            ->get();

        foreach ($automations as $automation) {
            $hasActiveRun = AutomationRun::query()
                ->where('automation_id', $automation->id)
                ->where('customer_id', $customer->id)
                ->where('status', 'running')
                ->exists();

            if ($hasActiveRun) {
                continue;
            }

            $automationService->trigger($automation, $customer, ['cart_id' => $cart->id]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('MarkAbandonedCartsJob failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
