<?php

namespace Modules\Remarketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuppressionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
