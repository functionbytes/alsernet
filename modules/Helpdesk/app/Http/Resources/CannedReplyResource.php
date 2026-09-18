<?php

namespace Modules\Helpdesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CannedReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'shortcut' => $this->shortcut,
            'category' => $this->category,
            'content' => substr(strip_tags($this->html_body ?? $this->body ?? ''), 0, 200),
            'tags' => $this->tags ?? [],
            'isGlobal' => $this->is_global,
            'usageCount' => $this->usage_count,
        ];
    }
}
