<?php

namespace Modules\Engagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Engagement\Connectors\ConnectorFactory;
use Modules\Engagement\Contracts\CustomerDataConnector;
use Modules\Engagement\Models\PlatformIntegration;

/**
 * Orquesta consultas de datos de cliente a través de múltiples plataformas.
 *
 * Caso típico: el agente abre una conversación de Alvarez en el inbox.
 * El inbox tiene dos integraciones activas (PrestaShop + ERP). El orchestrator
 * descubre ambas y devuelve los datos agrupados por fuente para que el
 * right-panel los muestre lado a lado.
 */
class CustomerDataOrchestrator
{
    public function __construct(
        protected ConnectorFactory $factory,
    ) {}

    /**
     * Devuelve todas las integraciones activas para un inbox.
     *
     * @return Collection<int, PlatformIntegration>
     */
    public function integrationsForInbox(int $inboxId): Collection
    {
        return PlatformIntegration::query()
            ->where('inbox_id', $inboxId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Ejecuta una acción en TODAS las integraciones activas del inbox y
     * devuelve respuestas agrupadas por integration id.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     * @return array<int, array{ok: bool, data: array|null, error: string|null, cached_at: string|null, platform: string}>
     */
    public function fetchAcrossInbox(int $inboxId, string $action, array $lookup, bool $force = false): array
    {
        $integrations = $this->integrationsForInbox($inboxId);
        $results = [];

        foreach ($integrations as $integration) {
            try {
                $connector = $this->factory->for($integration);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            if (! $connector->supports($action)) {
                continue;
            }

            $results[$integration->id] = $this->callAction($connector, $integration, $action, $lookup, $force);
        }

        return $results;
    }

    /**
     * Ejecuta una acción en una integración específica.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function fetchSingle(PlatformIntegration $integration, string $action, array $lookup, bool $force = false): array
    {
        try {
            $connector = $this->factory->for($integration);
        } catch (\InvalidArgumentException $e) {
            return [
                'ok' => false,
                'data' => null,
                'error' => 'unsupported',
                'cached_at' => null,
                'platform' => $integration->platform,
            ];
        }

        if (! $connector->supports($action)) {
            return [
                'ok' => false,
                'data' => null,
                'error' => 'unsupported',
                'cached_at' => null,
                'platform' => $integration->platform,
            ];
        }

        return $this->callAction($connector, $integration, $action, $lookup, $force);
    }

    /**
     * Invalida cache de un cliente en todas las integraciones del inbox.
     * Llamado por PlatformWebhookHandler tras procesar un webhook entrante.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    public function invalidateAcrossInbox(int $inboxId, array $lookup): void
    {
        foreach ($this->integrationsForInbox($inboxId) as $integration) {
            try {
                $this->factory->for($integration)->invalidateCache($integration, $lookup);
            } catch (\InvalidArgumentException) {
                continue;
            }
        }
    }

    /**
     * Resuelve la llamada al método correcto del connector según action name.
     *
     * @param  array{email?: string|null, external_id?: string|null}  $lookup
     */
    protected function callAction(
        CustomerDataConnector $connector,
        PlatformIntegration $integration,
        string $action,
        array $lookup,
        bool $force,
    ): array {
        return match ($action) {
            'profile' => $connector->profile($integration, $lookup, $force),
            'orders' => $connector->orders($integration, $lookup, $force),
            'orderDetail' => $this->safeOrderDetail($connector, $integration, $lookup, $force),
            'addresses' => $connector->addresses($integration, $lookup, $force),
            'returns' => $connector->returns($integration, $lookup, $force),
            'vouchers' => $connector->vouchers($integration, $lookup, $force),
            'cart' => $connector->cart($integration, $lookup, $force),
            'messages' => $connector->messages($integration, $lookup, $force),
            'deliveryNotes' => $connector->deliveryNotes($integration, $lookup, $force),
            'invoices' => $connector->invoices($integration, $lookup, $force),
            'payments' => $connector->payments($integration, $lookup, $force),
            'debts' => $connector->debts($integration, $lookup, $force),
            'balance' => $connector->balance($integration, $lookup, $force),
            'bonuses' => $connector->bonuses($integration, $lookup, $force),
            'loyaltyPoints' => $connector->loyaltyPoints($integration, $lookup, $force),
            default => [
                'ok' => false,
                'data' => null,
                'error' => "unknown action: {$action}",
                'cached_at' => null,
                'platform' => $integration->platform,
            ],
        };
    }

    private function safeOrderDetail(
        CustomerDataConnector $connector,
        PlatformIntegration $integration,
        array $lookup,
        bool $force,
    ): array {
        $orderId = $lookup['order_id'] ?? null;
        if (empty($orderId) || $orderId === '0' || $orderId === 0) {
            return [
                'ok' => false,
                'data' => null,
                'error' => 'missing order_id',
                'cached_at' => null,
                'platform' => $integration->platform,
            ];
        }

        return $connector->orderDetail($integration, $orderId, $force);
    }
}
