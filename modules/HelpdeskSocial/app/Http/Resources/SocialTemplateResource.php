<?php

namespace Modules\HelpdeskSocial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'platform' => $this->platform,
            'body' => $this->body,
            'variables' => $this->variables,
            'quick_replies' => $this->quick_replies,
            'category' => $this->category,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'usage_count' => $this->usage_count,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
