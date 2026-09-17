<?php

namespace Modules\HelpdeskSocial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'social_comment_id' => $this->social_comment_id,
            'body' => $this->body,
            'type' => $this->type,
            // El modelo User de este proyecto no tiene columna `name` (siempre
            // NULL) — usa firstname/lastname, como el resto de Helpdesk.
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => trim($this->user->firstname.' '.$this->user->lastname),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
