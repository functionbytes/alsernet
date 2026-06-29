<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Remarketing\Mail\DoubleOptinMail;
use Modules\Remarketing\Models\Customer;

class SendDoubleOptinMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 30;

    public function __construct(
        private readonly Customer $customer
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(): void
    {
        $customer = $this->customer->fresh();

        if (! $customer || ! $customer->email) {
            return;
        }

        // Only send if still pending (not already confirmed or unsubscribed)
        if ($customer->status !== 'pending') {
            return;
        }

        Mail::to($customer->email)->send(new DoubleOptinMail($customer));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendDoubleOptinMailJob failed', [
            'customer_id' => $this->customer->id,
            'customer_email' => $this->customer->email,
            'error' => $e->getMessage(),
        ]);
    }
}
