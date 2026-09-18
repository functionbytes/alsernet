<?php

namespace Modules\Helpdesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversationId' => $this->conversation_id,
            'type' => $this->type,
            'body' => $this->body,
            'htmlBody' => $this->html_body,
            'isInternal' => (bool) $this->is_internal,
            'attachmentUrls' => $this->attachment_urls ?? [],
            'metadata' => $this->metadata ?? [],
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
                'type' => 'customer',
            ]),
            'agent' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'type' => 'agent',
            ] : null),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
