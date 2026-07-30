<?php

namespace Modules\HelpdeskTickets\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'isOpen' => (bool) $this->is_open,
            'isDefault' => (bool) $this->is_default,
        ];
    }
}
