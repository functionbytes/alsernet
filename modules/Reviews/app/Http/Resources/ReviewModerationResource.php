<?php

namespace Modules\Reviews\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewModerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'isVisible' => $this->is_visible,
            'isFeatured' => $this->is_featured,
            'tags' => $this->tags ?? [],
            'internalNotes' => $this->when(
                $request->user()?->can('moderate', $this->review),
                fn () => $this->internal_notes
            ),
            'moderatedBy' => $this->moderated_by,
            'moderatedAt' => $this->moderated_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
