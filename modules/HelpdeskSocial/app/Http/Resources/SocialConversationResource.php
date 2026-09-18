<?php

namespace Modules\HelpdeskSocial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'social_account_id' => $this->social_account_id,
            'platform' => $this->platform,
            'external_conversation_id' => $this->external_conversation_id,
            'external_post_id' => $this->external_post_id,
            'participant_external_id' => $this->participant_external_id,
            'participant_name' => $this->participant_name,
            'status' => $this->status,
            'message_count' => $this->message_count,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'account' => $this->whenLoaded('account', fn () => new SocialAccountResource($this->account)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
