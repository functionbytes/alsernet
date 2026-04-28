<?php

namespace Modules\Campaign\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'attributes' => $this->attributes ?? [],
            'verification_status' => $this->verification_status,
            'subscribed_at' => $this->subscribed_at,
            'unsubscribed_at' => $this->unsubscribed_at,
            'created_at' => $this->created_at,
            // Estado por lista (cuando viene del pivot)
            'pivot' => $this->whenPivotLoaded('campaign_maillists_subscribers', fn () => [
                'status' => $this->pivot->status,
                'subscribed_at' => $this->pivot->subscribed_at,
                'unsubscribed_at' => $this->pivot->unsubscribed_at,
            ]),
        ];
    }
}
