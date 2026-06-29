<?php

declare(strict_types=1);

namespace Modules\Engagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Engagement\Models\MobileDevice;
use Modules\Engagement\Services\PushNotificationService;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public MobileDevice $device,
        public array $payload,
    ) {}

    public function handle(PushNotificationService $service): void
    {
        $service->sendToDevice($this->device, $this->payload);
    }

    public function failed(\Throwable $exception): void
    {
        $this->device->update(['push_enabled' => false]);
        report($exception);
    }
}
