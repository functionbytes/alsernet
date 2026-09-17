<?php

namespace Modules\HelpdeskSocial\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialApprovalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'social_comment_id' => $this->social_comment_id,
            'requested_by_user_id' => $this->requested_by_user_id,
            // El modelo User de este proyecto no tiene columna `name` (siempre
            // NULL) — usa firstname/lastname, como el resto de Helpdesk.
            'requester' => $this->whenLoaded('requester', fn () => [
                'id' => $this->requester->id,
                'name' => trim($this->requester->firstname.' '.$this->requester->lastname),
            ]),
            'approver_user_id' => $this->approver_user_id,
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'name' => trim($this->approver->firstname.' '.$this->approver->lastname),
            ]),
            'action_type' => $this->action_type,
            'payload' => $this->payload,
            'status' => $this->status,
            'approver_note' => $this->approver_note,
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
