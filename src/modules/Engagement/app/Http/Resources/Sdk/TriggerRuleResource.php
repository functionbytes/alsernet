<?php

namespace Modules\Engagement\Http\Resources\Sdk;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TriggerRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'conditions' => $this->conditions,
            'action' => $this->action,
            'priority' => $this->priority,
            'firesPerSession' => $this->fires_per_session,
        ];
    }
}
