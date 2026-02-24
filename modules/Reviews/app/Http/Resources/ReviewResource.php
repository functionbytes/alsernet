<?php

namespace Modules\Reviews\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'googleReviewId' => $this->google_review_id,
            'reviewerName' => $this->reviewer_name,
            'reviewerPhotoUrl' => $this->reviewer_photo_url,
            'rating' => $this->star_rating->value(),
            'ratingLabel' => $this->star_rating->name,
            'comment' => $this->comment,
            'reviewTime' => $this->review_time->toIso8601String(),
            'updateTime' => $this->update_time?->toIso8601String(),
            'googleReplyText' => $this->google_reply_text,
            'googleReplyTime' => $this->google_reply_time?->toIso8601String(),
            'hasGoogleReply' => $this->hasGoogleReply(),
            'hasComment' => ! is_null($this->comment),
            'syncedAt' => $this->synced_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
            'location' => ReviewLocationResource::make($this->whenLoaded('location')),
            'moderation' => $this->when(
                $this->relationLoaded('moderation') && $request->user()?->can('viewAny', \Modules\Reviews\Models\Review::class),
                fn () => ReviewModerationResource::make($this->moderation)
            ),
            'replies' => ReviewReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}
