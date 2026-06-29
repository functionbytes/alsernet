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
            'type' => $data['type'] ?? null,
            'title' => $data['title'] ?? 'Notificación',
            'message' => $data['message'] ?? '',
            'icon' => $data['icon'] ?? 'fas fa-bell',
            'color' => $data['color'] ?? 'primary',
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? 'Ver',
            'priority' => $data['priority'] ?? 'normal',
            'entity_id' => $data['entity_id'] ?? null,
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->diffForHumans(),
            'created_at_full' => $this->created_at?->toIso8601String(),
        ];
    }
}
