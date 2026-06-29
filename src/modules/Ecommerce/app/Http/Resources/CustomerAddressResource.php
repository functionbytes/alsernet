<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'address' => $this->address,
            'zip_code' => $this->zip_code,
            'is_default' => $this->is_default,
            'type' => $this->type,
            'created_at' => $this->created_at,
        ];
    }
}
