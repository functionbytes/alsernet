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
            'userId' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'startTime' => $this->start_time,
            'endTime' => $this->end_time,
            'dayOfWeek' => $this->day_of_week,
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
