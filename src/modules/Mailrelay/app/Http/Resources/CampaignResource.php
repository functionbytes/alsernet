<?php

namespace Modules\Mailrelay\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
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
            'mailrelay_campaign_id' => $this->mailrelay_campaign_id,
            'name' => $this->name,
            'subject' => $this->subject,
            'status' => $this->status,
            'recipient_count' => $this->recipient_count,
            'list' => [
                'id' => $this->list_id,
                'name' => $this->whenLoaded('list', fn () => $this->list->name),
                'description' => $this->whenLoaded('list', fn () => $this->list->description),
            ],
            'analytics' => $this->whenLoaded('analytics', function () {
                return [
                    'sent_count' => $this->analytics->sent_count,
                    'opened_count' => $this->analytics->opened_count,
                    'clicked_count' => $this->analytics->clicked_count,
                    'bounced_count' => $this->analytics->bounced_count,
                    'unsubscribed_count' => $this->analytics->unsubscribed_count,
                    'open_rate' => $this->analytics->open_rate,
                    'click_rate' => $this->analytics->click_rate,
                    'last_synced_at' => $this->analytics->last_synced_at?->toIso8601String(),
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
