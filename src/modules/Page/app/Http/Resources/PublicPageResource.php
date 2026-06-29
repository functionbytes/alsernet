<?php

namespace Modules\Page\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPageResource extends JsonResource
{
    /**
     * Transform the resource into an array, exposing only safe public fields.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'excerpt' => $this->getExcerpt(),
            'template' => $this->template,
            'url' => $this->url,
            'featured_image' => $this->featured_image,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
