<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Ecommerce\Models\Subscription;

class ProcessSubscriptionRenewalsCommand extends Command
{
    protected $signature = 'ecommerce:process-subscription-renewals';

    protected $description = 'Procesa renovaciones de suscripciones que vencen hoy';

    public function handle(): int
    {
        $renewed = 0;

        Subscription::query()
            ->where('status', 'active')
            ->where('next_billing_at', '<=', now())
            ->with(['customer', 'product'])
            ->chunk(50, function ($subscriptions) use (&$renewed) {
                foreach ($subscriptions as $subscription) {
                    $this->processRenewal($subscription, $renewed);
                }
            });

        $this->info("Suscripciones renovadas: {$renewed}");

        return self::SUCCESS;
    }

    private function processRenewal(Subscription $subscription, int &$renewed): void
    {
        try {
            Log::info('Subscription renewed', [
                'id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'amount' => $subscription->price,
            ]);

            $subscription->update([
                'last_billed_at' => now(),
                'next_billing_at' => $subscription->calculateNextBilling(),
            ]);

            $this->notifyCustomer($subscription);

            $renewed++;
        } catch (\Throwable $e) {
            Log::error('Subscription renewal failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyCustomer(Subscription $subscription): void
    {
        if (! $subscription->customer) {
            return;
        }

        $customer = $subscription->customer;
        $productName = $subscription->product?->name ?? 'tu producto';
        $nextDate = $subscription->next_billing_at->format('d/m/Y');

        Mail::raw(
            "Hola {$customer->name},\n\nTu suscripción a \"{$productName}\" ha sido renovada.\nMonto: \${$subscription->price}\nPróximo cobro: {$nextDate}",
            fn ($m) => $m->to($customer->email)->subject('Renovación de suscripción procesada')
        );
    }
}
