<?php

namespace Modules\Mailing\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Mailing\Enums\CampaignStatus;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mails_campaigns';

    protected $fillable = [
        'mailrelay_campaign_id',
        'name',
        'subject',
        'html_content',
        'text_content',
        'sender_id',
        'status',
        'scheduled_at',
        'sent_at',
        'recipient_count',
        'metadata',
        'list_id',
    ];

    protected $casts = [
        'status' => CampaignStatus::class,
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function analytics(): HasOne
    {
        return $this->hasOne(CampaignAnalytics::class);
    }

    public function markAsSending(): void
    {
        $this->update([
            'status' => CampaignStatus::SENDING,
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => CampaignStatus::SENT,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => CampaignStatus::FAILED,
        ]);
    }

    public function schedule(\DateTimeInterface $scheduledAt): void
    {
        $this->update([
            'status' => CampaignStatus::SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function isDraft(): bool
    {
        return $this->status === CampaignStatus::DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === CampaignStatus::SENT;
    }

    public function scopeDraft($query)
    {
        return $query->where('status', CampaignStatus::DRAFT);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', CampaignStatus::SCHEDULED);
    }

    public function scopeSent($query)
    {
        return $query->where('status', CampaignStatus::SENT);
    }
}
