<?php

namespace Modules\HelpdeskIntegration\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerExternalId;
use Modules\HelpdeskIntegration\Services\CustomerIntegrationService;

/**
 * Reverifica en background los vínculos que el sync síncrono del modal no
 * pudo procesar dentro de su presupuesto de tiempo (quedaron en estado
 * 'pending'). Cada vínculo se resincroniza igual que en el request original:
 * actualiza sync_status/last_synced_at y deja rastro en el audit log.
 */
class ResyncCustomerIntegrationsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    /**
     * @param  array<int, int>  $linkIds
     */
    public function __construct(
        private readonly int $customerId,
        private readonly array $linkIds,
    ) {}

    public function handle(CustomerIntegrationService $integrations): void
    {
        $customer = Customer::query()->find($this->customerId);

        if (! $customer) {
            return;
        }

        $links = CustomerExternalId::query()
            ->where('customer_id', $this->customerId)
            ->whereIn('id', $this->linkIds)
            ->get();

        foreach ($links as $link) {
            $integrations->resyncLink($customer, $link);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ResyncCustomerIntegrationsJob failed', [
            'customer_id' => $this->customerId,
            'link_ids' => $this->linkIds,
            'error' => $exception->getMessage(),
        ]);
    }
}
