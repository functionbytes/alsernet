<?php

namespace Modules\Remarketing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;
use Modules\Remarketing\Jobs\SendWelcomeMailJob;
use Modules\Remarketing\Models\Customer;

class SendWelcomeOnFirstOrderListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'remarketing';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(PsOrderCreated $event): void
    {
        $customer = Customer::query()
            ->where('external_id', $event->customerId())
            ->first();

        if (! $customer) {
            return;
        }

        if ((int) $customer->orders_count !== 1) {
            return;
        }

        $this->logTrigger('welcome_first_order', $customer, $event->payload, true);

        SendWelcomeMailJob::dispatch($customer->id);
    }

    public function failed(PsOrderCreated $event, \Throwable $exception): void
    {
        Log::error('SendWelcomeOnFirstOrderListener failed', [
            'customer_id' => $event->customerId(),
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
