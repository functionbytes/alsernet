<?php

namespace Modules\Campaign\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaillistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'description' => $this->description,
            'from_email' => $this->from_email,
            'from_name' => $this->from_name,
            'default_subject' => $this->default_subject,
            'subscribe_confirmation' => (bool) $this->subscribe_confirmation,
            'send_welcome_email' => (bool) $this->send_welcome_email,
            'cached_subscriber_count' => $this->cached_subscriber_count ?? 0,
            'subscribers_count' => $this->whenCounted('subscribers'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
