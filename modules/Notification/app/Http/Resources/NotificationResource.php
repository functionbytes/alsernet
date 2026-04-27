<?php

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'data' => [
                'type' => $data['type'] ?? null,
                'title' => $data['title'] ?? 'Notificación',
                'message' => $data['message'] ?? '',
                'icon' => $data['icon'] ?? 'fas fa-bell',
                'color' => $data['color'] ?? 'primary',
                'actionUrl' => $data['action_url'] ?? null,
                'actionText' => $data['action_text'] ?? 'Ver',
                'priority' => $data['priority'] ?? 'normal',
                'entityId' => $data['entity_id'] ?? null,
            ],
            'read' => $this->read_at !== null,
            'readAt' => $this->read_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'createdAtHuman' => $this->created_at?->diffForHumans(),
        ];
    }
}
