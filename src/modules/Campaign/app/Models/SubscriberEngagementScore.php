<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriberEngagementScore extends Model
{
    protected $table = 'campaign_subscriber_engagement_scores';

    protected $fillable = [
        'subscriber_id',
        'score',
        'opens_30d',
        'clicks_30d',
        'sent_30d',
        'bounces_90d',
        'last_opened_at',
        'last_clicked_at',
    ];

    protected $casts = [
        'last_opened_at' => 'datetime',
        'last_clicked_at' => 'datetime',
    ];

    public function subscriber()
    {
        return $this->belongsTo(CampaignSubscriber::class, 'subscriber_id');
    }

    public function category(): string
    {
        return match (true) {
            $this->score >= 70 => 'hot',
            $this->score >= 40 => 'warm',
            $this->score >= 10 => 'cold',
            $this->score >= -20 => 'dormant',
            default => 'at_risk',
        };
    }
}
