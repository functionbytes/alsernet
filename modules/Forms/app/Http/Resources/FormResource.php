<?php

namespace Modules\Forms\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'is_active' => (bool) $this->is_active,
            'is_multi_step' => (bool) $this->is_multi_step,
            'submit_button_text' => $this->submit_button_text,
            'theme' => $this->theme,
            'button_position' => $this->button_position,
            'button_size' => $this->button_size,
            'success_animation' => $this->success_animation,
            'shortcode' => $this->shortcode_tag,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'fields' => FormFieldResource::collection($this->whenLoaded('fields')),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
        ];
    }
}
