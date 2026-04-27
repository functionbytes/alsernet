<?php

namespace Modules\Locations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'phoneCode' => $this->phone_code,
            'currencyCode' => $this->currency_code,
            'flag' => $this->flag_emoji,
            'isActive' => $this->is_active,
            'statesCount' => $this->whenCounted('states'),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
