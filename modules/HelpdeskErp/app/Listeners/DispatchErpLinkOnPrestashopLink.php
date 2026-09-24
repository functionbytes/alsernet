<?php

namespace Modules\HelpdeskErp\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\CustomerExternalId;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;

/**
 * Observer de Eloquent sobre CustomerExternalId (lo registra
 * HelpdeskErpServiceProvider): en cuanto un contacto del helpdesk queda
 * vinculado a un cliente de PrestaShop, se intenta vincularlo también con
 * Gestión.
 *
 * Ni HelpdeskPrestashop ni HelpdeskIntegration emiten un evento propio al
 * vincular (todos acaban en Customer::linkExternalId(), un updateOrCreate),
 * así que el único punto común es el 'created' del modelo.
 *
 * El id de PrestaShop es CODIGO_INTERNET en Gestión: con él el vínculo es
 * exacto (ErpCustomerLinkerService lo prueba el primero).
 */
class DispatchErpLinkOnPrestashopLink
{
    public function created(CustomerExternalId $link): void
    {
        if ($link->platform !== 'prestashop' || $link->customer_id === null) {
            return;
        }

        // En tests la cola es 'sync': sin este interruptor, cada test de otro
        // módulo que vincula un cliente de PrestaShop lanzaría el linker en
        // línea contra el manager real. Un test que quiera probarlo lo activa.
        if (! (bool) config('helpdeskErp.link.auto_on_prestashop', ! app()->runningUnitTests())) {
            return;
        }

        // «Ajustes de Gestión» → vinculación automática desactivada. El job
        // va con force (para saltar el enfriamiento), así que el corte del
        // propio job no lo pararía: se corta aquí.
        if (! config('helpdeskErp.auto_link', true)) {
            return;
        }

        try {
            if (! function_exists('helpdesk_erp_enabled') || ! helpdesk_erp_enabled()) {
                return;
            }

            $alreadyLinked = CustomerExternalId::query()
                ->where('customer_id', $link->customer_id)
                ->where('platform', 'erp')
                ->exists();

            if ($alreadyLinked) {
                return;
            }

            // force: el vínculo con PrestaShop es un identificador nuevo, así
            // que el enfriamiento de una búsqueda anterior fallida (24 h si
            // no se encontró) no aplica. afterCommit: si el vínculo se escribe
            // dentro de una transacción, el job no debe correr antes de que
            // la fila exista para los demás procesos.
            LinkCustomerToErpJob::dispatch((int) $link->customer_id, null, null, true)->afterCommit();
        } catch (\Throwable $e) {
            // Vincular con Gestión es un extra: nunca debe romper el vínculo
            // con PrestaShop que lo ha provocado.
            Log::warning('HelpdeskErp: no se pudo encolar la vinculación tras vincular PrestaShop.', [
                'customer_id' => $link->customer_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
