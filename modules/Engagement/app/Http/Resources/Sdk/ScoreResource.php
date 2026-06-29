<?php

namespace Modules\Engagement\Http\Resources\Sdk;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'score' => $this->score,
            'segment' => $this->segment,
            'lastEventAt' => $this->last_event_at?->toIso8601String(),
        ];
    }
}
