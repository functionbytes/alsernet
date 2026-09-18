<?php

namespace Modules\HelpdeskErp\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerContextResource extends JsonResource
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly array $data,
        private readonly ?string $email = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->data['customer'] ?? ['found' => false];
        $orders = $this->data['orders'] ?? [];
        $invoices = $this->data['invoices'] ?? [];
        $ordersLoading = (bool) ($this->data['orders_loading'] ?? false);

        return [
            'customer' => $this->normalizeCustomer($customer),
            'orders' => array_map([$this, 'normalizeOrder'], $orders),
            'invoices' => array_map([$this, 'normalizeInvoice'], $invoices),
            'ordersLoading' => $ordersLoading,
            'meta' => $this->buildMeta($ordersLoading, $this->email),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMeta(bool $ordersLoading, ?string $email): array
    {
        $meta = ['retryAfter' => $this->data['meta']['retry_after'] ?? null];

        if ($ordersLoading && $email !== null) {
            $meta['broadcastChannel'] = 'private-erp-orders-ready.'.md5(strtolower(trim($email)));
            $meta['broadcastEvent'] = '.erp.orders.ready';
        }

        $error = $this->data['_error'] ?? null;
        if ($error) {
            $meta['error_type'] = $error['type'] ?? null;
            $meta['error_message'] = $error['message'] ?? null;
        }

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    private function normalizeCustomer(array $customer): array
    {
        if (! ($customer['found'] ?? false)) {
            return ['found' => false];
        }

        return [
            'found' => true,
            'id' => $customer['id'] ?? null,
            'name' => $customer['name'] ?? null,
            'email' => $customer['email'] ?? null,
            'nif' => $customer['nif'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'city' => $customer['city'] ?? null,
            'province' => $customer['province'] ?? null,
            'credit_limit' => $customer['credit_limit'] ?? null,
            'balance' => $customer['balance_pending'] ?? null,
            'balance_invoiced' => $customer['balance_invoiced'] ?? null,
            'loyalty_points' => $customer['loyalty_points'] ?? null,
            'payment_terms' => $customer['payment_terms'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    private function normalizeOrder(array $order): array
    {
        return [
            'id' => $order['id'] ?? null,
            'number' => $order['number'] ?? null,
            'status' => $order['status'] ?? null,
            'date' => $this->toIso($order['date'] ?? null),
            'expected_date' => $this->toIso($order['expected_date'] ?? null),
            'served_date' => $this->toIso($order['served_date'] ?? null),
            'warehouse' => $order['warehouse'] ?? null,
            'type' => $order['type'] ?? null,
            'observations' => $order['observations'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @return array<string, mixed>
     */
    private function normalizeInvoice(array $invoice): array
    {
        return [
            'id' => $invoice['id'] ?? null,
            'number' => $invoice['number'] ?? null,
            'series' => $invoice['series'] ?? null,
            'year' => $invoice['year'] ?? null,
            'date' => $this->toIso($invoice['date'] ?? null),
            'status' => $invoice['status'] ?? null,
            'simplified' => $invoice['simplified'] ?? false,
            'payment_method' => $invoice['payment_method'] ?? null,
        ];
    }

    private function toIso(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }
}
