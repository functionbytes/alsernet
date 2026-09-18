<?php

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->code,
            'name' => $this->name,
            'available' => $this->available,
            'webPublished' => $this->web_published,
            'isDefault' => $this->is_default,
            'supplierId' => $this->supplier_id,
            'categoryId' => $this->category_id,
            'lastSyncAt' => $this->last_sync_at?->toIso8601String(),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'attributes' => $this->whenLoaded('attributes'),
            'translations' => $this->whenLoaded('translations'),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
