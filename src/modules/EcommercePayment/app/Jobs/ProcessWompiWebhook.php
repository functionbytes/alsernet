<?php

namespace Modules\EcommercePayment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\EcommercePayment\Services\WompiGateway;

class ProcessWompiWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public array $transaction) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(WompiGateway $gateway): void
    {
        Log::info('Processing Wompi webhook job', [
            'reference' => $this->transaction['reference'] ?? 'unknown',
            'transaction_id' => $this->transaction['id'] ?? 'unknown',
        ]);

        $result = $gateway->processWebhookTransaction($this->transaction);

        if (! $result['success']) {
            Log::error('Wompi webhook job failed', $result);
            throw new \RuntimeException('Webhook processing failed: '.$result['message']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Wompi webhook job permanently failed', [
            'reference' => $this->transaction['reference'] ?? 'unknown',
            'transaction_id' => $this->transaction['id'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
