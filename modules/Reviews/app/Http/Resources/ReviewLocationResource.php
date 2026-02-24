<?php

namespace Modules\Reviews\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'googleLocationId' => $this->google_location_id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'websiteUrl' => $this->website_url,
            'averageRating' => (float) number_format($this->average_rating, 2),
            'totalReviews' => $this->total_reviews,
            'isVerified' => $this->is_verified,
            'isActive' => $this->is_active,
            'syncedAt' => $this->synced_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
