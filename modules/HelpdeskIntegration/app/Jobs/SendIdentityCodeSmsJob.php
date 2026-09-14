<?php

namespace Modules\HelpdeskIntegration\Jobs;

use App\Helpers\PiiMasker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Services\SmsService;

class SendIdentityCodeSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly string $to,
        private readonly string $message,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(SmsService $sms): void
    {
        $sms->send($this->to, $this->message);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendIdentityCodeSmsJob failed', [
            'to' => PiiMasker::phone($this->to),
            'error' => $exception->getMessage(),
        ]);
    }
}
