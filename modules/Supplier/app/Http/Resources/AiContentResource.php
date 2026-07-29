<?php

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'supplierId' => $this->supplier_id,
            'supplierProductId' => $this->supplier_product_id,
            'erpReference' => $this->erp_reference,
            'ean' => $this->ean,
            'status' => $this->status,
            'statusLabel' => $this->contentStatus?->label ?? $this->status,
            'statusClass' => $this->contentStatus?->badge_class ?? 'bg-secondary',
            'generatedName' => $this->generated_name,
            'shortDescription' => $this->short_description,
            'longDescription' => $this->long_description,
            'seoTitle' => $this->seo_title,
            'seoDescription' => $this->seo_description,
            'seoKeywords' => $this->seo_keywords,
            'promptId' => $this->prompt_id,
            'promptLabel' => $this->whenLoaded('prompt', fn () => $this->prompt?->label),
            'supplierLabel' => $this->whenLoaded('supplier', fn () => $this->supplier?->label),
            'validatedBy' => $this->validated_by,
            'validatedAt' => $this->validated_at?->toIso8601String(),
            'publishedAt' => $this->published_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
