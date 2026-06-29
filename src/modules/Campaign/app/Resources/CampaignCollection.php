<?php

namespace Modules\Campaign\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * API Resource Collection for Campaign entities.
 *
 * Transforms multiple Campaign models for JSON API responses.
 */
class CampaignCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = CampaignResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
