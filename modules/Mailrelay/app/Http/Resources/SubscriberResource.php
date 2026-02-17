<?php

namespace Modules\Mailrelay\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'status' => $this->status,
            'mailrelay_id' => $this->mailrelay_id,
            'list' => $this->whenLoaded('groups', function () {
                return $this->groups->first();
            }),
            'groups' => $this->whenLoaded('groups', function () {
                return $this->groups;
            }),
            'custom_fields' => $this->custom_fields,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
