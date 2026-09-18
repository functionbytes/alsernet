<?php

namespace Modules\HelpdeskSocial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'platform' => $this->platform,
            'account_type' => $this->account_type,
            'external_id' => $this->external_id,
            'username' => $this->username,
            'profile_url' => $this->profile_url,
            'is_active' => $this->is_active,
            'comments_enabled' => $this->comments_enabled,
            'messages_enabled' => $this->messages_enabled,
            'auto_reply_enabled' => $this->auto_reply_enabled,
            'consecutive_failures' => $this->consecutive_failures,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'last_error_at' => $this->last_error_at?->toIso8601String(),
            'token_expires_at' => $this->token_expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
