<?php

namespace Modules\HelpdeskSocial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'author_name' => $this->author_name,
            'author_username' => $this->author_username,
            'body' => $this->body,
            'intent' => $this->intent,
            'intent_confidence' => $this->intent_confidence,
            'urgency' => $this->urgency,
            'status' => $this->status,
            'reply_type' => $this->reply_type,
            'reply_body' => $this->reply_body,
            'replied_at' => $this->replied_at?->toIso8601String(),
            'is_mention' => $this->is_mention,
            'is_spam' => $this->is_spam,
            'is_hidden' => $this->is_hidden,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assigned_user' => $this->whenLoaded('assignedUser', fn () => [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
            ]),
            'social_account' => $this->whenLoaded('socialAccount', fn () => new SocialAccountResource($this->socialAccount)),
            'posted_at' => $this->posted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
