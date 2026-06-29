<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Remarketing\Mail\OrderAnniversaryMail;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Order;
use Modules\Remarketing\Models\Suppression;

class SendOrderAnniversaryMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 30;

    public function __construct(
        private readonly int $customerId,
        private readonly int $orderId,
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(): void
    {
        $customer = Customer::find($this->customerId);

        if (! $customer || ! $customer->email) {
            return;
        }

        if (Suppression::query()
            ->where('store_id', $customer->store_id)
            ->where('email', strtolower(trim($customer->email)))
            ->exists()
        ) {
            $this->logTrigger($customer, 'suppressed');

            return;
        }

        $order = Order::query()->with('items')->find($this->orderId);

        if (! $order) {
            $this->logTrigger($customer, 'order_not_found');

            return;
        }

        try {
            Mail::to($customer->email)->send(new OrderAnniversaryMail($customer, $order));
            $this->logTrigger($customer, null, true);
        } catch (\Throwable $e) {
            Log::warning('SendOrderAnniversaryMailJob failed', [
                'customer_id' => $customer->id,
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);
            $this->logTrigger($customer, 'send_error');
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendOrderAnniversaryMailJob permanently failed', [
            'customer_id' => $this->customerId,
            'order_id' => $this->orderId,
            'error' => $e->getMessage(),
        ]);
    }

    private function logTrigger(Customer $customer, ?string $skipReason, bool $sent = false): void
    {
        DB::table('remarketing_automation_triggers_log')->insert([
            'trigger_type' => 'order_anniversary',
            'store_id' => $customer->store_id,
            'customer_id' => $customer->id,
            'context' => json_encode(['order_id' => $this->orderId]),
            'email_sent' => $sent,
            'skip_reason' => $skipReason,
            'triggered_at' => now(),
        ]);
    }
}
