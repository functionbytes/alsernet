<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Log;

/**
 * Looks up a real order from the ERP or PrestaShop for an identified customer,
 * and normalizes the result so a chat flow node can present it. Reuses the
 * HelpdeskErp / HelpdeskPrestashop context services when installed.
 */
class ChatFlowOrderLookup
{
    /**
     * @param  object|null  $erp  HelpdeskErp ErpContextService (optional)
     * @param  object|null  $ps  HelpdeskPrestashop PrestashopContextService (optional)
     */
    public function __construct(
        private readonly ?object $erp = null,
        private readonly ?object $ps = null,
    ) {}

    /**
     * @param  array{erp_id?: int|string|null, ps_id?: int|string|null, email?: string|null}  $customer
     * @return array{found: bool, order_id: int|string|null, status: string|null, date: string|null, total: string|null, tracking: string|null, source: string|null, raw: array<string,mixed>}
     */
    public function lookup(int|string|null $orderId, array $customer, string $source = 'auto'): array
    {
        $orderId = is_numeric($orderId) ? (int) $orderId : null;

        if (! $orderId) {
            return $this->notFound();
        }

        $erpId = $customer['erp_id'] ?? null;
        $psId = $customer['ps_id'] ?? null;
        $email = $customer['email'] ?? null;

        $tryErp = in_array($source, ['auto', 'erp'], true) && $this->erp && $erpId;
        $tryPs = in_array($source, ['auto', 'ps'], true) && $this->ps && ($psId || $email);

        if ($tryErp) {
            $order = $this->fromErp((int) $erpId, $orderId);
            if ($order['found']) {
                return $order;
            }
        }

        if ($tryPs) {
            $order = $this->fromPs($orderId, $psId ? (int) $psId : null, $email);
            if ($order['found']) {
                return $order;
            }
        }

        return $this->notFound();
    }

    private function fromErp(int $customerId, int $orderId): array
    {
        try {
            $raw = $this->erp->getOrderDetail($customerId, $orderId);
        } catch (\Throwable $e) {
            Log::warning('ChatFlowOrderLookup: ERP getOrderDetail failed', ['error' => $e->getMessage()]);

            return $this->notFound();
        }

        return $raw ? $this->normalize($raw, 'erp') : $this->notFound();
    }

    private function fromPs(int $orderId, ?int $psCustomerId, ?string $email): array
    {
        try {
            $raw = $this->ps->getOrderDetail($orderId, $email);
        } catch (\Throwable $e) {
            Log::warning('ChatFlowOrderLookup: PS getOrderDetail failed', ['error' => $e->getMessage()]);

            return $this->notFound();
        }

        return $raw ? $this->normalize($raw, 'ps') : $this->notFound();
    }

    /**
     * @param  array<string,mixed>  $raw
     */
    private function normalize(array $raw, string $source): array
    {
        // The detail payload may be nested under `order` or `data`.
        $order = $raw['order'] ?? $raw['data'] ?? $raw;

        return [
            'found' => true,
            'order_id' => $order['id'] ?? $order['order_id'] ?? $order['reference'] ?? null,
            'status' => $this->firstValue($order, ['status', 'state', 'current_state', 'estado']),
            'date' => $this->firstValue($order, ['date', 'date_add', 'created_at', 'fecha']),
            'total' => $this->firstValue($order, ['total', 'total_paid', 'total_amount', 'importe']),
            'tracking' => $this->firstValue($order, ['tracking', 'tracking_number', 'shipping_number', 'seguimiento']),
            'source' => $source,
            'raw' => $order,
        ];
    }

    /**
     * @param  array<string,mixed>  $arr
     * @param  array<int,string>  $keys
     */
    private function firstValue(array $arr, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($arr[$key]) && $arr[$key] !== '' && ! is_array($arr[$key])) {
                return (string) $arr[$key];
            }
        }

        return null;
    }

    private function notFound(): array
    {
        return [
            'found' => false,
            'order_id' => null,
            'status' => null,
            'date' => null,
            'total' => null,
            'tracking' => null,
            'source' => null,
            'raw' => [],
        ];
    }
}
