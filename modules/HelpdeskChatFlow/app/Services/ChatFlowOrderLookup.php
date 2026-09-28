<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatDescriptions;

/**
 * Looks up a real order from the ERP or PrestaShop for an identified customer,
 * and normalizes the result so a chat flow node (or the AI agent's
 * `lookup_order` tool) can present it. Reuses the HelpdeskErp / HelpdeskPrestashop
 * context services when installed.
 *
 * Accepts either a numeric order id or a PrestaShop-style alphanumeric
 * reference (e.g. "XKBKNABJK"); references are only resolved against
 * PrestaShop and always re-verified against the customer's own email.
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
     * @return array{found: bool, order_id: int|string|null, reference: string|null, status: string|null, status_date: string|null, date: string|null, total: string|null, currency: string|null, carrier: string|null, tracking: string|null, tracking_number: string|null, tracking_url: string|null, expected_date: string|null, shipped_date: string|null, items: array<int,array{name:string,quantity:int}>, source: string|null, raw: array<string,mixed>}
     */
    public function lookup(int|string|null $orderRef, array $customer, string $source = 'auto'): array
    {
        [$orderId, $reference] = $this->resolveOrderRef($orderRef);

        if ($orderId === null && $reference === null) {
            return $this->notFound();
        }

        $erpId = $customer['erp_id'] ?? null;
        $psId = $this->normalizeId($customer['ps_id'] ?? null);
        $email = $customer['email'] ?? null;

        $wantsErp = in_array($source, ['auto', 'erp'], true) && $this->erp && $erpId;
        $wantsPs = in_array($source, ['auto', 'ps'], true) && $this->ps;

        // References only exist in PrestaShop and are always re-verified by
        // email — never fall back to id-only ownership for them (fail closed
        // if the customer's email is unknown).
        if ($reference !== null) {
            if ($wantsPs && $email) {
                $order = $this->fromPsReference($reference, $email, $psId);
                if ($order['found']) {
                    return $order;
                }
            } else {
                Log::info('ChatFlowOrderLookup: búsqueda por reference omitida, falta email verificado', [
                    'reference' => $reference,
                ]);
            }

            return $this->notFound();
        }

        if ($wantsErp) {
            $order = $this->fromErp((int) $erpId, $orderId);
            if ($order['found']) {
                return $order;
            }
        }

        if ($wantsPs && ($email || $psId)) {
            $order = $this->fromPs($orderId, $email, $psId);
            if ($order['found']) {
                return $order;
            }
        } elseif ($wantsPs) {
            Log::info('ChatFlowOrderLookup: PS lookup omitido, no se conoce email ni ps_id del cliente', [
                'order_id' => $orderId,
            ]);
        }

        return $this->notFound();
    }

    /**
     * Latest orders of the customer, for the bot to list when they don't
     * know the exact order number. Prefers PrestaShop (the reliable, fully
     * normalized source); falls back to the ERP order list if PS has
     * nothing or isn't configured.
     *
     * @param  array{erp_id?: int|string|null, ps_id?: int|string|null, email?: string|null}  $customer
     * @return array<int, array{order_id: int|string|null, reference: string|null, date: string|null, status: string|null, total: string|null, currency: string|null}>
     */
    public function recentOrders(array $customer, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $email = $customer['email'] ?? null;
        $psId = $this->normalizeId($customer['ps_id'] ?? null);
        $erpId = $customer['erp_id'] ?? null;

        if ($this->ps && ($email || $psId)) {
            $orders = $this->recentPsOrders($email, $psId, $limit);
            if ($orders !== []) {
                return $orders;
            }
        }

        if ($this->erp && $email && $erpId) {
            return $this->recentErpOrders($email, (int) $erpId, $limit);
        }

        return [];
    }

    /**
     * Splits a caller-supplied order reference into a numeric order id or a
     * PrestaShop-style alphanumeric reference (never both).
     *
     * @return array{0: int|null, 1: string|null}
     */
    private function resolveOrderRef(int|string|null $orderRef): array
    {
        if (is_int($orderRef)) {
            return $orderRef > 0 ? [$orderRef, null] : [null, null];
        }

        if (! is_string($orderRef)) {
            return [null, null];
        }

        $trimmed = trim($orderRef);

        if ($trimmed === '') {
            return [null, null];
        }

        if (ctype_digit($trimmed)) {
            return [(int) $trimmed, null];
        }

        return $this->looksLikeReference($trimmed) ? [null, strtoupper($trimmed)] : [null, null];
    }

    private function looksLikeReference(string $value): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{6,12}$/', $value);
    }

    private function normalizeId(mixed $value): ?int
    {
        return $value !== null && $value !== '' && is_numeric($value) ? (int) $value : null;
    }

    private function fromErp(int $customerId, int $orderId): array
    {
        try {
            $raw = $this->erp->getOrderDetail($customerId, $orderId);
        } catch (\Throwable $e) {
            Log::warning('ChatFlowOrderLookup: ERP getOrderDetail failed', ['error' => $e->getMessage()]);

            return $this->notFound();
        }

        return $raw ? $this->normalizeErp($raw) : $this->notFound();
    }

    private function fromPs(int $orderId, ?string $email, ?int $psId): array
    {
        try {
            $raw = $this->ps->getOrderDetail($orderId, $email, $psId);
        } catch (\Throwable $e) {
            Log::warning('ChatFlowOrderLookup: PS getOrderDetail failed', ['error' => $e->getMessage()]);

            return $this->notFound();
        }

        return $raw ? $this->normalizePs($raw) : $this->notFound();
    }

    private function fromPsReference(string $reference, string $email, ?int $psId): array
    {
        try {
            $raw = method_exists($this->ps, 'getOrderDetailByReference')
                ? $this->ps->getOrderDetailByReference($reference, $email, $psId)
                : null;
        } catch (\Throwable $e) {
            Log::warning('ChatFlowOrderLookup: PS getOrderDetailByReference failed', ['error' => $e->getMessage()]);

            return $this->notFound();
        }

        return $raw ? $this->normalizePs($raw) : $this->notFound();
    }

    /**
     * @return array<int, array{order_id: int|string|null, reference: string|null, date: string|null, status: string|null, total: string|null, currency: string|null}>
     */
    private function recentPsOrders(?string $email, ?int $psId, int $limit): array
    {
        if (! $email) {
            return [];
        }

        try {
            $result = $this->ps->getCustomerOrders($email, $psId, $limit, 1);
        } catch (\Throwable $e) {
            Log::warning('ChatFlowOrderLookup: recentOrders PS lookup failed', ['error' => $e->getMessage()]);

            return [];
        }

        $orders = $result['data'] ?? $result['orders'] ?? [];

        return array_map(fn (array $o): array => [
            'order_id' => $o['id'] ?? null,
            'reference' => $o['reference'] ?? null,
            'date' => $o['created_at'] ?? $o['date_add'] ?? null,
            'status' => $o['state_name'] ?? null,
            'total' => $this->formatMoney($o['total'] ?? null),
            'currency' => $o['currency'] ?? null,
        ], array_slice($orders, 0, $limit));
    }

    /**
     * @return array<int, array{order_id: int|string|null, reference: string|null, date: string|null, status: string|null, total: string|null, currency: string|null}>
     */
    private function recentErpOrders(string $email, int $erpId, int $limit): array
    {
        try {
            $context = $this->erp->getCustomerContext($email, null, null, $erpId);
        } catch (\Throwable $e) {
            Log::warning('ChatFlowOrderLookup: recentOrders ERP lookup failed', ['error' => $e->getMessage()]);

            return [];
        }

        $orders = $context['orders'] ?? [];

        return array_map(fn (array $o): array => [
            'order_id' => $o['id'] ?? null,
            'reference' => $o['number'] ?? null,
            'date' => $o['date'] ?? null,
            'status' => $this->erpStatusText($o['status'] ?? null),
            'total' => null,
            'currency' => null,
        ], array_slice($orders, 0, $limit));
    }

    /**
     * @param  array<string,mixed>  $raw
     */
    private function normalizePs(array $raw): array
    {
        $order = $raw['order'] ?? $raw['data'] ?? $raw;

        $totals = is_array($order['totals'] ?? null) ? $order['totals'] : [];
        $tracking = $this->firstTracking(is_array($order['tracking'] ?? null) ? $order['tracking'] : []);
        $lastHistory = $this->lastHistoryEntry(is_array($order['history'] ?? null) ? $order['history'] : []);

        $status = $order['state_name'] ?? ($lastHistory['state_name'] ?? null);
        $trackingNumber = $tracking['tracking_number'] ?? null;

        return array_merge($this->emptyResult(), [
            'found' => true,
            'order_id' => $order['id'] ?? $order['order_id'] ?? null,
            'reference' => $order['reference'] ?? null,
            'status' => $status !== null && $status !== '' ? (string) $status : null,
            'status_date' => $lastHistory['date'] ?? null,
            'date' => $order['created_at'] ?? $order['date'] ?? null,
            'total' => isset($totals['total']) ? $this->formatMoney($totals['total']) : null,
            'currency' => $order['currency'] ?? null,
            'carrier' => $tracking['carrier_name'] ?? null,
            'tracking' => $trackingNumber,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $tracking['tracking_url'] ?? null,
            'expected_date' => null,
            'shipped_date' => $tracking['date'] ?? null,
            'items' => $this->normalizeItems($order['lines'] ?? [], 'name', 'quantity'),
            'source' => 'ps',
            'raw' => $this->sanitizeRaw($order),
        ]);
    }

    /**
     * @param  array<string,mixed>  $raw
     */
    private function normalizeErp(array $raw): array
    {
        $order = $raw['order'] ?? $raw['data'] ?? $raw;

        $statusRaw = $order['status'] ?? $order['status_code'] ?? null;
        $status = $order['status_description'] ?? $order['status_code_description'] ?? null;
        if ($status === null && $statusRaw !== null) {
            $status = $this->erpStatusText($statusRaw);
        }

        return array_merge($this->emptyResult(), [
            'found' => true,
            'order_id' => $order['id'] ?? $order['order_id'] ?? null,
            'reference' => $order['number'] ?? null,
            'status' => $status,
            'status_date' => null,
            'date' => $order['date'] ?? null,
            'total' => isset($order['total']) ? $this->formatMoney($order['total']) : null,
            'currency' => $order['currency'] ?? null,
            'carrier' => null,
            'tracking' => $order['tracking'] ?? null,
            'tracking_number' => $order['tracking'] ?? null,
            'tracking_url' => null,
            'expected_date' => $order['expected_date'] ?? null,
            'shipped_date' => $order['served_date'] ?? null,
            'items' => $this->normalizeItems($order['lines'] ?? [], 'name', 'qty'),
            'source' => 'erp',
            'raw' => $this->sanitizeRaw($order),
        ]);
    }

    /**
     * Numeric ERP status code (PEDIDOCLIESTADO.ESTADO) translated via the
     * config('helpdeskErp.chat_codes.order_status') fallback map; a
     * non-numeric value is assumed to already be readable text and is
     * passed through as-is. Returns null (never invents a label) when the
     * code has no known mapping.
     */
    private function erpStatusText(mixed $statusRaw): ?string
    {
        if ($statusRaw === null || $statusRaw === '') {
            return null;
        }

        if (! is_numeric($statusRaw)) {
            return (string) $statusRaw;
        }

        return class_exists(ErpChatDescriptions::class)
            ? ErpChatDescriptions::fallback('order_status', $statusRaw)
            : null;
    }

    /**
     * @param  array<int,array<string,mixed>>  $lines
     * @return array<int,array{name:string,quantity:int}>
     */
    private function normalizeItems(array $lines, string $nameKey, string $qtyKey): array
    {
        return array_values(array_map(fn (array $line): array => [
            'name' => $this->stripHtml((string) ($line[$nameKey] ?? '')),
            'quantity' => (int) ($line[$qtyKey] ?? 0),
        ], array_slice($lines, 0, 5)));
    }

    /**
     * @param  array<int,array<string,mixed>>  $trackingList
     * @return array<string,mixed>
     */
    private function firstTracking(array $trackingList): array
    {
        foreach ($trackingList as $entry) {
            if (is_array($entry) && ! empty($entry['tracking_number'])) {
                return $entry;
            }
        }

        return [];
    }

    /**
     * @param  array<int,array<string,mixed>>  $history
     * @return array<string,mixed>
     */
    private function lastHistoryEntry(array $history): array
    {
        $last = end($history);

        return is_array($last) ? $last : [];
    }

    private function stripHtml(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strip_tags($value)));
    }

    private function formatMoney(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? number_format((float) $value, 2, ',', '.') : (string) $value;
    }

    /**
     * Strips PII (addresses, phone numbers, payment details) that must never
     * leave the normalized result — `raw` is for internal debugging only and
     * the bot must not expose it to the customer.
     *
     * @param  array<string,mixed>  $order
     * @return array<string,mixed>
     */
    private function sanitizeRaw(array $order): array
    {
        unset(
            $order['shipping_address'],
            $order['billing_address'],
            $order['shipping_address_id'],
            $order['billing_address_id'],
            $order['payments'],
            $order['customer_email'],
            $order['customer_firstname'],
            $order['customer_lastname'],
            $order['phone'],
            $order['address'],
        );

        return $order;
    }

    private function emptyResult(): array
    {
        return [
            'found' => false,
            'order_id' => null,
            'reference' => null,
            'status' => null,
            'status_date' => null,
            'date' => null,
            'total' => null,
            'currency' => null,
            'carrier' => null,
            'tracking' => null,
            'tracking_number' => null,
            'tracking_url' => null,
            'expected_date' => null,
            'shipped_date' => null,
            'items' => [],
            'source' => null,
            'raw' => [],
        ];
    }

    private function notFound(): array
    {
        return $this->emptyResult();
    }
}
