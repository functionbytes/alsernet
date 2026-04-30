<?php

namespace Modules\HelpdeskAgents\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentShiftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agentId' => $this->agent_id,
            'agent' => $this->whenLoaded('agent', fn () => [
                'id' => $this->agent?->id,
                'name' => $this->agent?->name,
            ]),
            'startTime' => $this->start_time,
            'endTime' => $this->end_time,
            'dayOfWeek' => $this->day_of_week,
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
