<?php

namespace Modules\Locations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'stateId' => $this->state_id,
            'countryId' => $this->country_id,
            'isActive' => $this->is_active,
            'state' => $this->whenLoaded('state', fn () => [
                'id' => $this->state->id,
                'name' => $this->state->name,
            ]),
            'country' => $this->whenLoaded('country', fn () => [
                'id' => $this->country->id,
                'name' => $this->country->name,
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
