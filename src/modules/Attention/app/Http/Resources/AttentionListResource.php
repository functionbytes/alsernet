<?php

namespace Modules\Attention\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttentionListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'radicado' => $this->radicado,
            'subject' => $this->subject,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'statusColor' => $this->status->color(),
            'isAnonymous' => $this->is_anonymous,
            'customerName' => $this->full_name,
            'customerEmail' => $this->when(! $this->is_anonymous, $this->customer_email),
            'type' => $this->whenLoaded('type', fn () => [
                'id' => $this->type->id,
                'name' => $this->type->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'sede' => $this->whenLoaded('sede', fn () => [
                'id' => $this->sede->id,
                'name' => $this->sede->name,
            ]),
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null),
            'assignedUser' => $this->whenLoaded('assignedUser', fn () => $this->assignedUser ? [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
            ] : null),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
