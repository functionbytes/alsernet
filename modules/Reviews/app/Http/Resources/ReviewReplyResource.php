<?php

namespace Modules\Reviews\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'replyText' => $this->reply_text,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'errorMessage' => $this->when(
                $this->status->value === 'failed' && $request->user()?->can('viewAny', \Modules\Reviews\Models\Review::class),
                $this->error_message
            ),
            'errorCount' => $this->when(
                $this->status->value === 'failed' && $request->user()?->can('viewAny', \Modules\Reviews\Models\Review::class),
                $this->error_count
            ),
            'createdBy' => $this->created_by,
            'approvedBy' => $this->approved_by,
            'approvedAt' => $this->approved_at?->toIso8601String(),
            'publishedAt' => $this->published_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
