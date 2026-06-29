<?php

namespace Modules\Remarketing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\ConsentEvent;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Suppression;

class ProcessBounceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        public readonly int $storeId,
        public readonly string $email,
        public readonly string $type,
        public readonly ?int $messageId = null,
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(): void
    {
        if ($this->messageId) {
            $this->updateMessage();
        }

        $shouldSuppress = in_array($this->type, ['hard', 'complaint']);

        if (! $shouldSuppress) {
            return;
        }

        $reason = match ($this->type) {
            'hard' => 'hard_bounce',
            'complaint' => 'complaint',
            default => 'manual',
        };

        Suppression::firstOrCreate(
            ['store_id' => $this->storeId, 'email' => strtolower($this->email)],
            ['reason' => $reason, 'source_message_id' => $this->messageId]
        );

        $newStatus = $this->type === 'complaint' ? 'complained' : 'bounced';

        Customer::query()
            ->where('store_id', $this->storeId)
            ->where('email', strtolower($this->email))
            ->update(['status' => $newStatus]);

        $consentEventType = $this->type === 'complaint' ? 'complained' : 'bounced';

        ConsentEvent::create([
            'store_id' => $this->storeId,
            'email' => strtolower($this->email),
            'event_type' => $consentEventType,
            'source' => 'api',
            'occurred_at' => now(),
        ]);
    }

    private function updateMessage(): void
    {
        $message = Message::find($this->messageId);

        if (! $message) {
            return;
        }

        $updateData = match ($this->type) {
            'hard', 'soft' => [
                'status' => 'bounced',
                'bounced_at' => now(),
                'bounce_type' => $this->type,
            ],
            'complaint' => [
                'status' => 'complained',
                'complained_at' => now(),
            ],
            default => [],
        };

        if (! empty($updateData)) {
            $message->update($updateData);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessBounceJob failed', [
            'store_id' => $this->storeId,
            'email' => $this->email,
            'type' => $this->type,
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
        ]);
    }
}
