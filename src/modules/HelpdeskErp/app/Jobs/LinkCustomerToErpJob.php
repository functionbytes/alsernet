<?php

namespace Modules\HelpdeskErp\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;

class LinkCustomerToErpJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    /** Evita que el mismo cliente se vincule dos veces en 5 minutos. */
    public int $uniqueFor = 300;

    public function __construct(
        private readonly int $customerId,
    ) {
        $this->onQueue('helpdesk-erp');
    }

    public function uniqueId(): string
    {
        return (string) $this->customerId;
    }

    public function handle(ErpCustomerLinkerService $linker): void
    {
        $customer = Customer::on('helpdesk')
            ->with('externalIds')
            ->find($this->customerId);

        if ($customer === null) {
            return;
        }

        $linker->linkCustomer($customer);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('LinkCustomerToErpJob failed', [
            'customer_id' => $this->customerId,
            'error' => $exception->getMessage(),
        ]);
    }
}
