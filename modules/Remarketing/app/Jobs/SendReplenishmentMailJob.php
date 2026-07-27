<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Remarketing\Mail\ReplenishmentMail;
use Modules\Remarketing\Models\AutomationTriggerLog;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Suppression;

class SendReplenishmentMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 30;

    public function __construct(
        private readonly int $customerId,
        private readonly int $productId,
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

        try {
            Mail::to($customer->email)->send(new ReplenishmentMail($customer, $this->productId));
            $this->logTrigger($customer, null, true);
        } catch (\Throwable $e) {
            Log::warning('SendReplenishmentMailJob failed', [
                'customer_id' => $customer->id,
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
            ]);
            $this->logTrigger($customer, 'send_error');
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendReplenishmentMailJob permanently failed', [
            'customer_id' => $this->customerId,
            'product_id' => $this->productId,
            'error' => $e->getMessage(),
        ]);
    }

    private function logTrigger(Customer $customer, ?string $skipReason, bool $sent = false): void
    {
        AutomationTriggerLog::create([
            'trigger_type' => 'replenishment',
            'store_id' => $customer->store_id,
            'customer_id' => $customer->id,
            'context' => ['product_id' => $this->productId],
            'email_sent' => $sent,
            'skip_reason' => $skipReason,
            'triggered_at' => now(),
        ]);
    }
}
