<?php

namespace Modules\Remarketing\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Event;

class ProcessPixelEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        public readonly int $storeId,
        public readonly ?int $customerId,
        public readonly ?string $visitorId,
        public readonly string $type,
        public readonly array $properties,
        public readonly ?string $ip,
        public readonly ?string $ua,
        public readonly Carbon $occurredAt,
    ) {
        $this->onQueue('remarketing');
    }

    public function handle(): void
    {
        $customerId = $this->customerId;

        if ($this->type === 'identify' && isset($this->properties['email'])) {
            $customerId = $this->resolveCustomerByEmail($this->properties['email']);
        }

        Event::create([
            'store_id' => $this->storeId,
            'customer_id' => $customerId,
            'visitor_id' => $this->visitorId,
            'type' => $this->type,
            'properties' => $this->properties,
            'source' => 'pixel',
            'ip' => $this->ip,
            'user_agent' => $this->ua,
            'occurred_at' => $this->occurredAt,
            'received_at' => now(),
        ]);
    }

    private function resolveCustomerByEmail(string $email): ?int
    {
        $email = strtolower(trim($email));

        $customer = Customer::firstOrCreate(
            ['store_id' => $this->storeId, 'email' => $email],
            [
                'email_hash' => hash('sha256', $email),
                'first_name' => $this->properties['first_name'] ?? null,
                'status' => 'pending',
            ]
        );

        if ($this->visitorId) {
            $this->mergeVisitorEvents($customer->id);
        }

        return $customer->id;
    }

    private function mergeVisitorEvents(int $customerId): void
    {
        Event::query()
            ->where('visitor_id', $this->visitorId)
            ->whereNull('customer_id')
            ->update(['customer_id' => $customerId]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPixelEventJob failed', [
            'store_id' => $this->storeId,
            'type' => $this->type,
            'visitor_id' => $this->visitorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
