<?php

namespace Modules\HelpdeskSocial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialMentionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'external_id' => $this->external_id,
            'author_name' => $this->author_name,
            'author_username' => $this->author_username,
            'body' => $this->body,
            'url' => $this->url,
            'matched_keywords' => $this->matched_keywords,
            'sentiment' => $this->sentiment,
            'sentiment_score' => $this->sentiment_score,
            'engagement_count' => $this->engagement_count,
            'status' => $this->status,
            'discovered_at' => $this->discovered_at?->toIso8601String(),
            'posted_at' => $this->posted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
