<?php

namespace Modules\Remarketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'phone' => $this->phone,
            'country' => $this->country,
            'locale' => $this->locale,
            'externalId' => $this->external_id,
            'status' => $this->status,
            'consentMarketing' => $this->consent_marketing,
            'tags' => $this->tags,
            'attributes' => $this->attributes,
            'rfmRecency' => $this->rfm_recency,
            'rfmFrequency' => $this->rfm_frequency,
            'rfmMonetary' => $this->rfm_monetary,
            'clvHistorical' => $this->clv_historical,
            'ordersCount' => $this->orders_count,
            'lastOrderAt' => $this->last_order_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
