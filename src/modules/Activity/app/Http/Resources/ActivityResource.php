<?php

namespace Modules\Activity\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'event' => $this->event,
            'logName' => $this->log_name,
            'subject' => [
                'type' => $this->subject_type ? class_basename($this->subject_type) : null,
                'id' => $this->subject_id,
            ],
            'causer' => $this->whenLoaded('causer', fn () => [
                'id' => $this->causer?->id,
                'name' => $this->causer?->name ?? 'Sistema',
                'email' => $this->causer?->email ?? '',
            ]),
            'properties' => $this->properties?->toArray() ?? [],
            'createdAt' => $this->created_at?->toIso8601String(),
            'createdAtHuman' => $this->created_at?->diffForHumans(),
        ];
    }
}
