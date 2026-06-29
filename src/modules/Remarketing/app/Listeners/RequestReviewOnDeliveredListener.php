<?php

namespace Modules\Remarketing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\Remarketing\Jobs\SendReviewRequestMailJob;
use Modules\Remarketing\Models\Customer;

class RequestReviewOnDeliveredListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'remarketing';

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(PsOrderStatusChanged $event): void
    {
        if ($event->newStatus() !== 'delivered') {
            return;
        }

        $customer = Customer::query()
            ->where('external_id', $event->customerId())
            ->first();

        if (! $customer) {
            return;
        }

        $this->logTrigger('review_request', $customer, $event->payload, true);

        SendReviewRequestMailJob::dispatch($customer->id, $event->orderId())
            ->delay(now()->addDays(3));
    }

    public function failed(PsOrderStatusChanged $event, \Throwable $exception): void
    {
        Log::error('RequestReviewOnDeliveredListener failed', [
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
