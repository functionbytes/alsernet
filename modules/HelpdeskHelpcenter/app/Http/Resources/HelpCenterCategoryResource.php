<?php

namespace Modules\HelpdeskHelpcenter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HelpCenterCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'isSection' => (bool) $this->is_section,
            'position' => $this->position,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent?->id,
                'name' => $this->parent?->name,
            ]),
            'articlesCount' => $this->whenCounted('articles'),
            'sectionsCount' => $this->whenCounted('sections'),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
