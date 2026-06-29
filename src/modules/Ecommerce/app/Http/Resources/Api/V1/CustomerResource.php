<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class CustomerResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'emailVerifiedAt' => $this->iso($this->email_verified_at),
            'phone' => $this->phone,
            'avatarUrl' => $this->mediaUrl($this->avatar),
            'status' => $this->status?->value,
            'createdAt' => $this->iso($this->created_at),
            'updatedAt' => $this->iso($this->updated_at),
        ];
    }
}
