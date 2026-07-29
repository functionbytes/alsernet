<?php

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'code' => $this->code,
            'label' => $this->label,
            'email' => $this->email,
            'available' => $this->available,
            'priority' => $this->priority,
            'lastSyncAt' => $this->last_sync_at?->toIso8601String(),
            'productsCount' => $this->whenCounted('products'),
            'sourcesCount' => $this->whenCounted('sources'),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'sources' => $this->whenLoaded('sources'),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
