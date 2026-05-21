<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Remarketing\Mail\VipRewardMail;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Suppression;

class SendVipRewardMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 30;

    public function __construct(
        private readonly int $customerId,
        private readonly int $milestone,
        private readonly float $newLifetime,
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(): void
    {
        $customer = Customer::find($this->customerId);

        if (! $customer || ! $customer->email) {
            return;
        }

        // Guard: do not send the same milestone twice for this customer
        $alreadySent = DB::table('remarketing_automation_triggers_log')
            ->where('customer_id', $this->customerId)
            ->where('trigger_type', 'vip_reward')
            ->whereRaw("JSON_EXTRACT(context, '$.milestone') = ?", [$this->milestone])
            ->exists();

        if ($alreadySent) {
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
            Mail::to($customer->email)->send(
                new VipRewardMail($customer, $this->milestone, $this->newLifetime)
            );
            $this->logTrigger($customer, null, true);
        } catch (\Throwable $e) {
            Log::warning('SendVipRewardMailJob failed', [
                'customer_id' => $customer->id,
                'milestone' => $this->milestone,
                'error' => $e->getMessage(),
            ]);
            $this->logTrigger($customer, 'send_error');
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendVipRewardMailJob permanently failed', [
            'customer_id' => $this->customerId,
            'milestone' => $this->milestone,
            'error' => $e->getMessage(),
        ]);
    }

    private function logTrigger(Customer $customer, ?string $skipReason, bool $sent = false): void
    {
        DB::table('remarketing_automation_triggers_log')->insert([
            'trigger_type' => 'vip_reward',
            'store_id' => $customer->store_id,
            'customer_id' => $customer->id,
            'context' => json_encode([
                'milestone' => $this->milestone,
                'new_lifetime' => $this->newLifetime,
            ]),
            'email_sent' => $sent,
            'skip_reason' => $skipReason,
            'triggered_at' => now(),
        ]);
    }
}
