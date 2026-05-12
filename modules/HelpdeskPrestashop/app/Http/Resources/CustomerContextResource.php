<?php

namespace Modules\HelpdeskPrestashop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'ordersCount' => $c['orders_count'] ?? 0,
            'lastOrderAt' => $this->toIso($c['last_order_at'] ?? null),
            'ltv' => $c['ltv'] ?? 0,
        ];
    }

    private function formatOrder(array $o): array
    {
        return [
            'id' => $o['id'] ?? null,
            'reference' => $o['reference'] ?? null,
            'placedAt' => $this->toIso($o['placed_at'] ?? null),
            'currencySign' => $o['currency_sign'] ?? '€',
            'requiresDocumentation' => $o['requires_documentation'] ?? false,
            'saleType' => $o['sale_type'] ?? null,
            'paymentMethod' => $o['payment_method'] ?? null,
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
            'updatedAt' => $this->toIso($c['updated_at'] ?? null),
            'currencySign' => $c['currency_sign'] ?? '€',
            'isVirtual' => $c['is_virtual'] ?? false,
            'totals' => $c['totals'] ?? null,
            'items' => $c['items'] ?? [],
            'vouchers' => $c['vouchers'] ?? [],
            'fittingServices' => $c['fitting_services'] ?? [],
        ];
    }

    private function toIso(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        try {
            return (new \DateTime($date))->format(\DateTime::ATOM);
        } catch (\Throwable) {
            return $date;
        }
    }
}
