<?php

namespace Modules\HelpdeskCampaigns\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'content' => $this->content,
            'appearance' => $this->appearance,
            'conditions' => $this->conditions,
            'metadata' => $this->metadata,
            'publishedAt' => $this->published_at?->toIso8601String(),
            'endsAt' => $this->ends_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'impressionsCount' => $this->whenCounted('impressions'),
            'clicksCount' => $this->when(isset($this->clicks_count), $this->clicks_count),
            'urls' => [
                'show' => route('helpdesk.campaigns.show', $this->resource),
                'edit' => route('helpdesk.campaigns.edit', $this->resource),
                'statistics' => route('helpdesk.campaigns.statistics', $this->resource),
            ],
        ];
    }
}
