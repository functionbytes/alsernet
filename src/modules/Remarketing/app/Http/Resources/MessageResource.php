<?php

namespace Modules\Remarketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'subject' => $this->subject,
            'status' => $this->status,
            'provider' => $this->provider,
            'openedAt' => $this->opened_at?->toIso8601String(),
            'clickedAt' => $this->clicked_at?->toIso8601String(),
            'sentAt' => $this->sent_at?->toIso8601String(),
            'bouncedAt' => $this->bounced_at?->toIso8601String(),
            'complainedAt' => $this->complained_at?->toIso8601String(),
            'revenue' => $this->revenue,
        ];
    }
}
