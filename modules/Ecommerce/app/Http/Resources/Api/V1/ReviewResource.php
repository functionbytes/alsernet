<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class ReviewResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customerName' => $this->customer_name,
            'star' => $this->star,
            'comment' => $this->comment,
            'isVerifiedBuyer' => (bool) $this->is_verified_buyer,
            'images' => collect($this->images ?? [])->map(fn ($path) => $this->mediaUrl($path))->values(),
            'reply' => $this->reply,
            'repliedAt' => $this->iso($this->replied_at),
            'createdAt' => $this->iso($this->created_at),
        ];
    }
}
