<?php

namespace Modules\Attention\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttentionShowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'radicado' => $this->radicado,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'statusColor' => $this->status->color(),

            // Customer
            'isAnonymous' => $this->is_anonymous,
            'customerName' => $this->full_name,
            'customerEmail' => $this->when(! $this->is_anonymous, $this->customer_email),
            'customerCellphone' => $this->when(! $this->is_anonymous, $this->customer_cellphone),
            'customerDni' => $this->when(! $this->is_anonymous, $this->customer_dni),
            'customerAddress' => $this->when(! $this->is_anonymous, $this->customer_address),

            // Relations
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

            // Resolution
            'responseType' => $this->response_type?->value,
            'responseTypeLabel' => $this->response_type?->label(),
            'resolution' => $this->resolution,
            'resolvedAt' => $this->resolved_at?->toIso8601String(),
            'closedAt' => $this->closed_at?->toIso8601String(),
            'satisfactionRating' => $this->satisfaction_rating,

            // Activity
            'notes' => $this->whenLoaded('notes', fn () => $this->notes->map(fn ($note) => [
                'id' => $note->id,
                'content' => $note->content,
                'user' => $note->relationLoaded('user') ? ['id' => $note->user?->id, 'name' => $note->user?->name] : null,
                'createdAt' => $note->created_at?->toIso8601String(),
            ])),
            'actions' => $this->whenLoaded('actions', fn () => $this->actions->take(50)->map(fn ($action) => [
                'id' => $action->id,
                'action' => $action->action,
                'description' => $action->description,
                'user' => $action->relationLoaded('user') ? ['id' => $action->user?->id, 'name' => $action->user?->name] : null,
                'createdAt' => $action->created_at?->toIso8601String(),
            ])),

            // Flags
            'canBeEdited' => $this->canBeEdited(),
            'isResolved' => $this->isResolved(),
            'isClosed' => $this->isClosed(),

            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
