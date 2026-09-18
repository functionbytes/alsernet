<?php

namespace Modules\HelpdeskErp\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Events\CustomerErpResolved;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;

class LinkCustomerToErpJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    /** Evita que el mismo cliente se vincule dos veces en 5 minutos. */
    public int $uniqueFor = 300;

    /**
     * @param  'ticket'|'conversation'|null  $sourceType  Qué provocó la búsqueda.
     * @param  int|null  $sourceId  Id del ticket o conversación de origen.
     * @param  bool  $force  Reintento pedido a mano por un agente: ignora el
     *                       enfriamiento y vuelve a preguntar al ERP.
     */
    public function __construct(
        private readonly int $customerId,
        private readonly ?string $sourceType = null,
        private readonly ?int $sourceId = null,
        private readonly bool $force = false,
    ) {
        $this->onQueue('helpdesk-erp');
    }

    /**
     * El origen entra en la clave a propósito. Si solo fuera el cliente, un
     * segundo correo suyo dentro de la ventana de 5 minutos se descartaría
     * entero y ese ticket nunca recibiría su CustomerErpResolved — se quedaría
     * sin enrutar. Con el origen dentro, cada ticket tiene su trabajo; lo que
     * evita repetir la consulta al ERP es el enfriamiento de handle(), que es
     * donde debe estar.
     */
    public function uniqueId(): string
    {
        return implode(':', [
            $this->customerId,
            $this->sourceType ?? '-',
            $this->sourceId ?? '-',
            $this->force ? 'force' : '',
        ]);
    }

    public function handle(ErpCustomerLinkerService $linker): void
    {
        if (! helpdesk_erp_enabled()) {
            return;
        }

        $customer = Customer::on('helpdesk')
            ->with('externalIds')
            ->find($this->customerId);

        if ($customer === null) {
            return;
        }

        // Ya vinculado: no se consulta el ERP, pero sí se anuncia el resultado
        // — el ticket que acaba de entrar necesita el evento para enrutarse
        // aunque el vínculo se resolviera hace meses.
        $existing = $customer->externalIds->firstWhere('platform', 'erp');

        if ($existing !== null) {
            $this->announce((int) $existing->external_id, 'linked');

            return;
        }

        if (! $this->force && $this->withinCooldown($customer)) {
            Log::info('LinkCustomerToErpJob: dentro del enfriamiento, no se consulta el ERP', [
                'customer_id' => $customer->id,
                'status' => $customer->erp_lookup_status,
            ]);

            $this->announce(null, $customer->erp_lookup_status ?? 'not_found');

            return;
        }

        $erpId = $linker->linkCustomer($customer);

        $this->announce($erpId, $erpId !== null ? 'linked' : ($customer->refresh()->erp_lookup_status ?? 'not_found'));
    }

    /**
     * Un cliente que ya se buscó y no apareció no se vuelve a preguntar hasta
     * pasado el enfriamiento. La ventana es más corta si el intento anterior
     * murió por una caída del ERP: eso sí merece reintento pronto.
     */
    private function withinCooldown(Customer $customer): bool
    {
        if ($customer->erp_lookup_at === null || ! $customer->erpLookupFailed()) {
            return false;
        }

        $minutes = $customer->erp_lookup_status === 'error'
            ? (int) config('helpdeskErp.lookup_error_cooldown_minutes', 30)
            : (int) config('helpdeskErp.lookup_cooldown_minutes', 1440);

        return $customer->erp_lookup_at->gt(now()->subMinutes($minutes));
    }

    private function announce(?int $erpId, string $status): void
    {
        CustomerErpResolved::dispatch(
            $this->customerId,
            $erpId,
            $status,
            $this->sourceType,
            $this->sourceId,
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('LinkCustomerToErpJob failed', [
            'customer_id' => $this->customerId,
            'error' => $exception->getMessage(),
        ]);
    }
}
