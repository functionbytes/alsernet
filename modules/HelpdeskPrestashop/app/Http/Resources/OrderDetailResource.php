<?php

namespace Modules\HelpdeskPrestashop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    /**
     * @param  array<string, mixed>  $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $o = $this->resource;

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
