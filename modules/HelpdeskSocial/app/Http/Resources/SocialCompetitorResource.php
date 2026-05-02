<?php

namespace Modules\HelpdeskSocial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialCompetitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'social_account_id' => $this->social_account_id,
            'name' => $this->name,
            'platform' => $this->platform,
            'external_id' => $this->external_id,
            'username' => $this->username,
            'profile_url' => $this->profile_url,
            'is_active' => $this->is_active,
            'latest_metrics' => $this->whenLoaded('latestMetrics', fn () => $this->latestMetrics->map(fn ($m) => [
                'metric_type' => $m->metric_type,
                'value' => $m->value,
                'captured_at' => $m->captured_at?->toIso8601String(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
