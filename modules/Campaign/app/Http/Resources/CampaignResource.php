<?php

namespace Modules\Campaign\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'subject' => $this->subject,
            'type' => $this->type,
            'status' => $this->status,
            'from_email' => $this->from_email,
            'from_name' => $this->from_name,
            'reply_to' => $this->reply_to,
            'preheader' => $this->preheader,
            'tracking' => [
                'open' => (bool) $this->track_open,
                'click' => (bool) $this->track_click,
                'fbl' => (bool) $this->track_fbl,
            ],
            'sign_dkim' => (bool) $this->sign_dkim,
            'run_at' => $this->run_at,
            'delivery_at' => $this->delivery_at,
            'last_error' => $this->last_error,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
