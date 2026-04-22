<?php

namespace Modules\HelpdeskTickets\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriorityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'level' => $this->level,
            'color' => $this->color,
            'responseTimeHours' => $this->response_time_hours,
            'resolutionTimeHours' => $this->resolution_time_hours,
        ];
    }
}
