<?php

namespace Modules\HelpdeskSocial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialSlaPolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'response_time_minutes' => $this->response_time_minutes,
            'resolution_time_minutes' => $this->resolution_time_minutes,
            'platform' => $this->platform,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'conditions' => $this->conditions,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
