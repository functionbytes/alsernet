<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;
use Modules\HelpdeskPrestashop\Services\Ext\OpsmapStateMapApplier;

/**
 * Pedido de PrestaShop con estado nuevo → estado de la conversación/ticket
 * según el mapeo de estados (pieza 39).
 *
 * Síncrono a propósito: la cola 'default' de webadmin no tiene worker, y lo
 * que hace son unas pocas consultas locales. Un fallo se registra y NO se
 * relanza: el receptor del webhook devolvería 500, PrestaShop reintentaría
 * y las notas ya creadas se duplicarían.
 */
class OpsmapApplyOrderStateMapping
{
    public function __construct(
        private readonly OpsmapStateMapApplier $applier
    ) {}

    public function handle(PsOrderStatusChanged $event): void
    {
        try {
            $result = $this->applier->apply(
                $event->customerId(),
                $event->orderId(),
                $event->oldStatus() !== null ? (int) $event->oldStatus() : null,
                $event->newStatus() !== null ? (int) $event->newStatus() : null,
            );
        } catch (\Throwable $e) {
            Log::warning('opsmap: fallo aplicando el mapeo de estados', [
                'order_id' => $event->orderId(),
                'new_status' => $event->newStatus(),
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($result === null || (! $result['conversation_changed'] && $result['ticket_ids'] === [] && $result['notes'] === 0)) {
            return;
        }

        // Log aparte del de acciones de agentes: esto lo hace el sistema, no
        // alguien desde el panel, y no debe mezclarse en "Auditoría de
        // acciones".
        if (function_exists('activity')) {
            activity('helpdeskprestashop-automation')
                ->withProperties([
                    'order_id' => $event->orderId(),
                    'old_status' => $event->oldStatus(),
                    'new_status' => $event->newStatus(),
                ] + $result)
                ->log('ps.state_map.applied');
        }
    }
}
