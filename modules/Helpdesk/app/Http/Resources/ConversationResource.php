<?php

namespace Modules\Helpdesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'isArchived' => (bool) ($this->is_archived ?? false),
            'isClosed' => $this->closed_at !== null,
            'archivedAt' => $this->archived_at?->toIso8601String(),
            'closedAt' => $this->closed_at?->toIso8601String(),
            'assignedAt' => $this->assigned_at?->toIso8601String(),
            'status' => $this->whenLoaded('status', fn () => [
                'id' => $this->status?->id,
                'name' => $this->status?->name,
                'color' => $this->status?->color,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee?->id,
                'name' => $this->assignee?->name,
            ]),
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'tags' => ConversationTagResource::collection($this->whenLoaded('conversationTags')),
            'messagesCount' => $this->whenCounted('messages'),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
