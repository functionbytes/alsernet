<?php

namespace Modules\Helpdesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatarUrl' => $this->profile_photo_url ?? null,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
