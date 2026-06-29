<?php

namespace Modules\Mailrelay\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Mailrelay\Entities\Campaign;

/**
 * @mixin Campaign
 */
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
            'name' => $this->name,
            'subject' => $this->subject,

            // Content (only when explicitly requested to avoid large payloads)
            'html_content' => $this->when($request->has('include_content'), $this->html_content),
            'text_content' => $this->when($request->has('include_content'), $this->text_content),

            // Status and timestamps
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),

            // Recipients and tracking
            'recipient_count' => $this->recipient_count,
            'track_opens' => $this->track_opens,
            'track_clicks' => $this->track_clicks,

            // Template integration
            'mailer_template_id' => $this->mailer_template_id,
            'lang_id' => $this->lang_id,
            'mail_provider_id' => $this->mail_provider_id,
            'template_variables' => $this->template_variables,

            // Provider integration
            'provider_campaign_id' => $this->provider_campaign_id,
            'mailrelay_campaign_id' => $this->mailrelay_campaign_id,

            // Metadata
            'metadata' => $this->metadata,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships (only when loaded)
            'mailer_template' => $this->whenLoaded('mailerTemplate', function () {
                return [
                    'id' => $this->mailerTemplate->id,
                    'name' => $this->mailerTemplate->name,
                    'subject' => $this->mailerTemplate->subject ?? null,
                ];
            }),

            'language' => $this->whenLoaded('language', function () {
                return [
                    'id' => $this->language->id,
                    'name' => $this->language->name,
                    'code' => $this->language->iso_code ?? $this->language->code ?? null,
                ];
            }),

            'mail_provider' => $this->whenLoaded('mailProvider', function () {
                return new MailProviderResource($this->mailProvider);
            }),

            'analytics' => $this->whenLoaded('analytics', function () {
                return [
                    'sent' => $this->analytics->sent_count ?? 0,
                    'delivered' => $this->analytics->delivered_count ?? 0,
                    'opens' => $this->analytics->opens_count ?? 0,
                    'clicks' => $this->analytics->clicks_count ?? 0,
                    'bounces' => $this->analytics->bounces_count ?? 0,
                    'complaints' => $this->analytics->complaints_count ?? 0,
                    'open_rate' => $this->analytics->open_rate ?? 0,
                    'click_rate' => $this->analytics->click_rate ?? 0,
                ];
            }),

            // Computed attributes
            'is_sent' => $this->isSent(),
            'can_edit' => ! $this->isSent(),
            'can_send' => $this->status->value === 'draft',
        ];
    }
}
