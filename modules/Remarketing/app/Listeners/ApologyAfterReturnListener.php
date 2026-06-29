<?php

namespace Modules\Remarketing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsOrderReturned;
use Modules\Remarketing\Jobs\SendApologyDiscountMailJob;
use Modules\Remarketing\Models\Customer;

class ApologyAfterReturnListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'remarketing';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(PsOrderReturned $event): void
    {
        $customer = Customer::query()
            ->where('external_id', $event->customerId())
            ->first();

        if (! $customer) {
            return;
        }

        $this->logTrigger('apology_after_return', $customer, $event->payload, true);

        SendApologyDiscountMailJob::dispatch($customer->id, $event->orderId());
    }

    public function failed(PsOrderReturned $event, \Throwable $exception): void
    {
        Log::error('ApologyAfterReturnListener failed', [
            'customer_id' => $event->customerId(),
            'order_id' => $event->orderId(),
            'error' => $exception->getMessage(),
        ]);
    }

    private function logTrigger(string $type, Customer $customer, array $context, bool $emailSent): void
    {
        DB::table('remarketing_automation_triggers_log')->insert([
            'trigger_type' => $type,
            'store_id' => $customer->store_id,
            'customer_id' => $customer->id,
            'context' => json_encode($context),
            'email_sent' => $emailSent,
            'triggered_at' => now(),
        ]);
    }
}
