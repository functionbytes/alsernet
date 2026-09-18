<?php

namespace Modules\HelpdeskSocial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'platform' => $this->platform,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'stop_processing' => $this->stop_processing,
            'trigger_count' => $this->trigger_count,
            'last_triggered_at' => $this->last_triggered_at?->toIso8601String(),
            'valid_from' => $this->valid_from,
            'valid_until' => $this->valid_until,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
