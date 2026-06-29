<?php

namespace Modules\Remarketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subject' => $this->subject,
            'preheader' => $this->preheader,
            'fromName' => $this->from_name,
            'fromEmail' => $this->from_email,
            'status' => $this->status,
            'scheduledAt' => $this->scheduled_at?->toIso8601String(),
            'startedAt' => $this->started_at?->toIso8601String(),
            'completedAt' => $this->completed_at?->toIso8601String(),
            'recipientsTotal' => $this->recipients_total,
            'sent' => $this->sent,
            'delivered' => $this->delivered,
            'bounced' => $this->bounced,
            'opened' => $this->opened,
            'clicked' => $this->clicked,
            'unsubscribed' => $this->unsubscribed,
            'complained' => $this->complained,
            'revenue' => $this->revenue,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
