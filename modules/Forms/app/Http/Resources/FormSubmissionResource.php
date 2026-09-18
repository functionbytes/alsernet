<?php

namespace Modules\Forms\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Campos sensibles (ip_address, user_agent, is_spam) sólo visibles
        // para usuarios con permiso de gestión (no para consumidores API genéricos).
        $canSeeSensitive = $request->user()?->can('Forms.submissions.delete')
            || $request->user()?->can('Forms.forms.manage');

        return [
            'id' => $this->id,
            'form_id' => $this->form_id,
            'status' => $this->status,
            'is_read' => (bool) $this->is_read,
            'is_starred' => (bool) $this->is_starred,
            'submitted_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'utm' => [
                'source' => $this->utm_source,
                'medium' => $this->utm_medium,
                'campaign' => $this->utm_campaign,
                'term' => $this->utm_term,
                'content' => $this->utm_content,
            ],
            'country' => $this->country,
            'city' => $this->city,
            'time_to_complete' => $this->time_to_complete,

            'is_spam' => $this->when($canSeeSensitive, (bool) $this->is_spam),
            'ip_address' => $this->when($canSeeSensitive, $this->ip_address),
            'user_agent' => $this->when($canSeeSensitive, $this->user_agent),

            'assigned_to' => $this->whenLoaded('assignedTo', fn () => $this->assignedTo ? [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name ?? null,
            ] : null),

            'values' => $this->whenLoaded('values', fn () => $this->values->map(fn ($v) => [
                'field_key' => $v->field_key,
                'field_label' => $v->field_label,
                'field_type' => $v->field_type,
                'value' => $v->value,
            ])),
        ];
    }
}
