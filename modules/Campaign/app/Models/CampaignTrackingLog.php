<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignTrackingLog failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignTrackingLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignTrackingLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignTrackingLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignTrackingLog sent()
 *
 * @mixin \Eloquent
 */
class CampaignTrackingLog extends Model
{
    protected $table = 'campaign_tracking_logs';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BOUNCED = 'bounced';

    public const STATUS_FEEDBACK_ABUSE = 'feedback-abuse';

    public const STATUS_FEEDBACK_SPAM = 'feedback-spam';

    protected $fillable = ['mail_id', 'campaign_id', 'message_id', 'runtime_message_id', 'subscriber_id', 'sending_server_id', 'customer_id', 'status', 'error', 'auto_trigger_id', 'sub_account_id', 'mail_list_id'];

    public function customer()
    {
        return $this->belongsTo('Modules\Campaign\Models\Customer');
    }

    public function campaign()
    {
        return $this->belongsTo('Modules\Campaign\Models\Campaign');
    }

    public function mailList()
    {
        return $this->belongsTo('Modules\Campaign\Models\MailList');
    }

    public function sendingServer()
    {
        return $this->belongsTo('Modules\CampaignSendingServers\Models\SendingServer');
    }

    public function subscriber()
    {
        return $this->belongsTo('Modules\Campaign\Models\Subscriber');
    }

    public function openLogs()
    {
        return $this->hasMany(CampaignOpenLog::class, 'tracking_log_id');
    }

    public function clickLogs()
    {
        return $this->hasMany(CampaignClickLog::class, 'tracking_log_id');
    }

    public function unsubscribeLogs()
    {
        return $this->hasMany(CampaignUnsubscribeLog::class, 'tracking_log_id');
    }

    public static function getAll()
    {
        return self::select('tracking_logs.*');
    }

    public static function filter($request)
    {
        $query = self::select('tracking_logs.*');
        $query = $query->leftJoin('subscribers', 'subscribers.id', '=', 'tracking_logs.subscriber_id');
        $query = $query->leftJoin('campaigns', 'campaigns.id', '=', 'tracking_logs.campaign_id');
        $query = $query->leftJoin('sending_servers', 'sending_servers.id', '=', 'tracking_logs.sending_server_id');
        $query = $query->leftJoin('customers', 'customers.id', '=', 'tracking_logs.customer_id');

        // Keyword
        if (! empty(trim($request->keyword))) {
            foreach (explode(' ', trim($request->keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('campaigns.name', 'like', '%'.$keyword.'%')
                        ->orwhere('tracking_logs.status', 'like', '%'.$keyword.'%')
                        ->orwhere('sending_servers.name', 'like', '%'.$keyword.'%')
                        ->orwhere('subscribers.email', 'like', '%'.$keyword.'%');
                });
            }
        }

        // filters
        $filters = $request->all();
        if (! empty($filters)) {
            if (! empty($filters['campaign_uid'])) {
                $query = $query->where('campaigns.uid', '=', $filters['campaign_uid']);
            }
        }

        return $query;
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public static function search($request, $campaign = null)
    {
        $query = self::filter($request);

        if (isset($campaign)) {
            $query = $query->where('tracking_logs.campaign_id', '=', $campaign->id);
        }

        $query = $query->orderBy($request->sort_order, $request->sort_direction);

        return $query;
    }

    public static $itemsPerPage = 25;
}
