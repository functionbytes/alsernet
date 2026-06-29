<?php

namespace Modules\Remarketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'platform' => $this->platform,
            'domain' => $this->domain,
            'status' => $this->status,
            'lastSyncedAt' => $this->last_synced_at?->toIso8601String(),
            'settings' => $this->settings,
            'createdAt' => $this->created_at->toIso8601String(),
            'customersCount' => $this->whenCounted('customers'),
            'productsCount' => $this->whenCounted('products'),
            'ordersCount' => $this->whenCounted('orders'),
            'cartsCount' => $this->whenCounted('carts'),
            'campaignsCount' => $this->whenCounted('campaigns'),
        ];
    }
}
