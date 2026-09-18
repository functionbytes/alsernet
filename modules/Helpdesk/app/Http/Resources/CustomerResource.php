<?php

namespace Modules\Helpdesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatarUrl' => $this->avatar_url,
            'country' => $this->country,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'isBanned' => $this->is_banned,
            'isVerified' => $this->is_verified,
            'lastSeenAt' => $this->last_seen_at?->toIso8601String(),
            'ticketsCount' => $this->whenCounted('tickets'),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
