<?php

namespace Modules\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'url' => $this->url,
            'description' => $this->description,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'image' => $this->image,
            'views' => $this->views,
            'published_at' => $this->published_at?->toISOString(),
            'categories' => BlogCategoryResource::collection($this->whenLoaded('categories')),
            'tags' => BlogTagResource::collection($this->whenLoaded('tags')),
            'author' => $this->when($this->user_id, fn () => ['name' => $this->user?->name]),
        ];
    }
}
