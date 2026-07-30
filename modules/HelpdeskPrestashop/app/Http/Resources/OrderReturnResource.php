<?php

namespace Modules\HelpdeskPrestashop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReturnResource extends JsonResource
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
        $r = $this->resource;

        return [
            'returnId' => $r['return_id'] ?? null,
            'status' => $r['status'] ?? null,
            'orderId' => $r['order_id'] ?? null,
            'createdAt' => $this->toIso($r['created_at'] ?? null),
            'items' => $r['items'] ?? [],
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
