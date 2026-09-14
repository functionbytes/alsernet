<?php

namespace Modules\Helpdesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversationId' => $this->conversation_id,
            'type' => $this->type,
            'body' => $this->body,
            'isInternal' => (bool) $this->is_internal,
            'isFromCustomer' => (bool) $this->is_from_customer,
            'attachments' => $this->attachments ?? [],
            'readAt' => $this->read_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
