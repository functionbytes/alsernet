<?php

namespace Modules\Campaign\Entities;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

/**
 * @property-read \Modules\Campaign\Entities\Campaign|null $campaign
 * @property-read \Modules\Campaign\Entities\CampaignLink|null $campaignLink
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignWebhook click()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignWebhook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignWebhook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignWebhook open()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignWebhook query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignWebhook unsubscribe()
 *
 * @mixin \Eloquent
 */
class CampaignWebhook extends Model
{
    use HasFactory;
    use HasUid;

    public const TYPE_OPEN = 'open';

    public const TYPE_CLICK = 'click';

    public const TYPE_UNSUBSCRIBE = 'unsubscribe';

    public function campaignLink()
    {
        return $this->belongsTo('Modules\Campaign\Entities\CampaignLink');
    }

    public function campaign()
    {
        return $this->belongsTo('Modules\Campaign\Entities\Campaign');
    }

    public function createFromArray($params)
    {
        // init rule
        $rules = [
            'endpoint' => 'required|url',
        ];

        // fill
        $this->type = $params['type'];
        $this->endpoint = $params['endpoint'];

        if ($params['type'] == self::TYPE_CLICK) {
            // fill campaign link id
            if (isset($params['campaign_link_id'])) {
                $this->campaign_link_id = $params['campaign_link_id'];
            }

            // rule update
            $rules['campaign_link_id'] = 'required';
        }

        // make validator
        $validator = \Validator::make($params, $rules);

        // redirect if fails
        if ($validator->fails()) {
            return [$this, $validator];
        }

        $this->save();

        return [$this, $validator];
    }

    public function updateFromArray($params)
    {
        // init rule
        $rules = [
            'endpoint' => 'required|url',
        ];

        // fill
        $this->endpoint = $params['endpoint'];

        // make validator
        $validator = \Validator::make($params, $rules);

        // redirect if fails
        if ($validator->fails()) {
            return [$this, $validator];
        }

        $this->save();

        return [$this, $validator];
    }

    public function execute($log) // OpenLog | ClickLog | UnsubscribeLog
    {
        $attributes = $this->makePostData4Callback($log);
        $response = Http::post('http://example.com/users', $attributes);
    }

    public function makePostData4Callback($log, $test = false) // OpenLog | ClickLog | UnsubscribeLog
    {
        $attributes = $log->getAttributes();
        $attributes['campaign'] = $this->campaign->getAttributes();
        $attributes['subscriber'] = $log->trackingLog->subscriber->getAttributes();
        $attributes['mail_list'] = $log->trackingLog->mailList;

        return $attributes;
    }

    public function scopeOpen($query)
    {
        return $query->where('type', self::TYPE_OPEN);
    }

    public function scopeClick($query)
    {
        return $query->where('type', self::TYPE_CLICK);
    }

    public function scopeUnsubscribe($query)
    {
        return $query->where('type', self::TYPE_UNSUBSCRIBE);
    }

    public function isOpen()
    {
        return $this->type == self::TYPE_OPEN;
    }

    public function isClick()
    {
        return $this->type == self::TYPE_CLICK;
    }

    public function isUnsubscribe()
    {
        return $this->type == self::TYPE_UNSUBSCRIBE;
    }
}
