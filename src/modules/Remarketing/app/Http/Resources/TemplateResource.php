<?php

namespace Modules\Remarketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'subject' => $this->subject,
            'preheader' => $this->preheader,
            'htmlContent' => $this->when(
                $request->routeIs('api.remarketing.templates.show'),
                $this->html_content
            ),
            'thumbnailUrl' => $this->thumbnail_url,
            'isGlobal' => $this->is_global,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
