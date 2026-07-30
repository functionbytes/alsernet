<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerCommerceSyncService;

/**
 * Enlaza al cliente con PrestaShop y gestión fuera del ciclo de render.
 * Se despacha de forma idempotente desde el panel de la conversación con un
 * guard de caché para evitar una llamada externa síncrona en cada repintado.
 */
class SyncCustomerCommerceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    public function __construct(
        private readonly Customer $customer
    ) {
        $this->onQueue('helpdesk');
    }

    public function handle(CustomerCommerceSyncService $sync): void
    {
        $customer = $this->customer->fresh();

        if (! $customer || ! $customer->email) {
            return;
        }

        $sync->sync($customer);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SyncCustomerCommerceJob failed', [
            'customer_id' => $this->customer->id,
            'error' => $e->getMessage(),
        ]);
    }
}
