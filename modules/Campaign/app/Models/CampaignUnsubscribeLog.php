<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignUnsubscribeLog extends Model
{
    protected $table = 'campaign_unsubscribe_logs';

    protected $fillable = [
        'tracking_log_id',
        'subscriber_id',
        'ip',
        'reason',
    ];

    public function trackingLog()
    {
        return $this->belongsTo(CampaignTrackingLog::class, 'tracking_log_id');
    }

    public function subscriber()
    {
        return $this->belongsTo(CampaignSubscriber::class, 'subscriber_id');
    }
}
