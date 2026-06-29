<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Log de feedback (queja de spam) ligado a un tracking_log de campaña.
 * NOTE: distinto del FeedbackLog del módulo CampaignSendingServers (este
 * está ligado al campaign tracking; el otro a un FBL handler IMAP).
 */
class CampaignFeedbackLog extends Model
{
    protected $table = 'campaign_feedback_logs';

    protected $fillable = [
        'tracking_log_id',
        'email',
        'description',
    ];

    public function trackingLog()
    {
        return $this->belongsTo(CampaignTrackingLog::class, 'tracking_log_id');
    }
}
