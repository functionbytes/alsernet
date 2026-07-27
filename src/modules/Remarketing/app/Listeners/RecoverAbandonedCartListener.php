<?php

namespace Modules\Remarketing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\Remarketing\Jobs\SendCartRecoveryMailJob;
use Modules\Remarketing\Models\Customer;

class RecoverAbandonedCartListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'remarketing';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(PsCartAbandoned $event): void
    {
        $email = $event->email();
        if (! $email) {
            return;
        }

        $customer = Customer::query()->where('email', $email)->first();
        if (! $customer) {
            return;
        }

        $alreadySent = DB::table('remarketing_automation_triggers_log')
            ->where('customer_id', $customer->id)
            ->where('trigger_type', 'cart_abandoned')
            ->where('triggered_at', '>=', now()->subHours(24))
            ->exists();

        if ($alreadySent) {
            return;
        }

        SendCartRecoveryMailJob::dispatch($customer->id, $event->payload);
    }

    public function failed(PsCartAbandoned $event, \Throwable $exception): void
    {
        Log::error('RecoverAbandonedCartListener failed', [
            'cart_id' => $event->cartId(),
            'email_hash' => hash('sha256', (string) $event->email()),
            'error' => $exception->getMessage(),
        ]);
    }
}
