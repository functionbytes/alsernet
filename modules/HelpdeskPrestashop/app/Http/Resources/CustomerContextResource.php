<?php

namespace Modules\HelpdeskPrestashop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class CustomerContextResource extends JsonResource
{
    /**
     * @param  array{customer: array, orders: array, carts: array}  $resource
     */
    public function __construct(array $resource)
    {
        // Bypass Eloquent model wrapping — resource is a plain array
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'customer' => $this->formatCustomer($this->resource['customer'] ?? []),
            'orders' => array_map([$this, 'formatOrder'], $this->resource['orders'] ?? []),
            'carts' => array_map([$this, 'formatCart'], $this->resource['carts'] ?? []),
        ];
    }

    private function formatCustomer(array $c): array
    {
        if (! ($c['found'] ?? false)) {
            return ['found' => false];
        }

        return [
            'found' => true,
            'id' => $c['id'] ?? null,
            'firstname' => $c['firstname'] ?? null,
            'lastname' => $c['lastname'] ?? null,
            'email' => $c['email'] ?? null,
            'orders_count' => $c['orders_count'] ?? 0,
            'last_order_at' => $this->toIso($c['last_order_at'] ?? null),
            'ltv' => $c['ltv'] ?? 0,
        ];
    }

    private function formatOrder(array $o): array
    {
        return [
            'id' => $o['id'] ?? null,
            'reference' => $o['reference'] ?? null,
            'placed_at' => $this->toIso($o['placed_at'] ?? null),
            'currency_sign' => $o['currency_sign'] ?? '€',
            'requires_documentation' => $o['requires_documentation'] ?? false,
            'sale_type' => $o['sale_type'] ?? null,
            'payment_method' => $o['payment_method'] ?? null,
            'state' => $o['state'] ?? null,
            'totals' => $o['totals'] ?? null,
            'lines' => $o['lines'] ?? [],
            'discounts' => $o['discounts'] ?? [],
            'payments' => $o['payments'] ?? [],
            'tracking' => $o['tracking'] ?? [],
        ];
    }

    private function formatCart(array $c): array
    {
        return [
            'id' => $c['id'] ?? null,
            'updated_at' => $this->toIso($c['updated_at'] ?? null),
            'currency_sign' => $c['currency_sign'] ?? '€',
            'is_virtual' => $c['is_virtual'] ?? false,
            'totals' => $c['totals'] ?? null,
            'items' => $c['items'] ?? [],
            'vouchers' => $c['vouchers'] ?? [],
            'fitting_services' => $c['fitting_services'] ?? [],
        ];
    }

    private function toIso(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        try {
            return (new \DateTime($date))->format(\DateTime::ATOM);
        } catch (\Throwable $e) {
            Log::debug('HelpdeskPrestashop: malformed date passed through Resource.', [
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return $date;
        }
    }
}
