<?php

namespace Modules\Remarketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'trigger' => $this->trigger,
            'triggerConfig' => $this->trigger_config,
            'status' => $this->status,
            'runsTotal' => $this->runs_total,
            'steps' => AutomationStepResource::collection($this->whenLoaded('steps')),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
