<?php

namespace Modules\Campaign\Entities;

use Modules\Campaign\Library\BaseCampaign;
use Modules\Campaign\Library\Contracts\CampaignInterface;
use Modules\Campaign\Library\Contracts\HasTemplateInterface;
use Modules\Campaign\Library\Traits\HasTemplate;

/**
 * @property int $id
 * @property string $uid
 * @property string $type
 * @property string $title
 * @property string|null $subject
 * @property string|null $plain
 * @property string|null $from_email
 * @property string|null $from_name
 * @property string|null $reply_to
 * @property string|null $status
 * @property int|null $sign_dkim
 * @property int|null $track_open
 * @property int|null $track_click
 * @property int|null $resend
 * @property string|null $run_at
 * @property string|null $delivery_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $template_source
 * @property string|null $last_error
 * @property string|null $image
 * @property int|null $default_maillist_id
 * @property int|null $tracking_domain_id
 * @property int|null $running_pid
 * @property int|null $template_id
 * @property int $use_default_sending_server_from_email
 * @property string|null $preheader
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Campaign\Entities\CampaignLink> $campaignLinks
 * @property-read int|null $campaign_links_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Campaign\Entities\CampaignWebhook> $campaignWebhooks
 * @property-read int|null $campaign_webhooks_count
 * @property-read \App\Models\User|null $customer
 * @property-read \Modules\Campaign\Entities\CampaignMaillist|null $defaultMailList
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Horizon\Models\JobMonitor> $jobMonitors
 * @property-read int|null $job_monitors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Campaign\Entities\CampaignListsSegment> $listsSegments
 * @property-read int|null $lists_segments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Campaign\Entities\CampaignMaillist> $mailLists
 * @property-read int|null $mail_lists_count
 * @property-read \Modules\Campaign\Models\Template\Template|null $template
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign byStatus($status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign filter($request)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign scheduled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign search($keyword)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereDefaultMaillistId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereDeliveryAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereFromEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereFromName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereLastError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign wherePlain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign wherePreheader($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereReplyTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereResend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereRunAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereRunningPid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereSignDkim($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTemplateSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTrackClick($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTrackOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTrackingDomainId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereUseDefaultSendingServerFromEmail($value)
 *
 * @mixin \Eloquent
 */
class Campaign extends BaseCampaign implements CampaignInterface, HasTemplateInterface
{
    use HasTemplate;

    public const TYPE_REGULAR = 'regular';

    public const TYPE_PLAIN_TEXT = 'plain-text';

    public const DELIVERY_STATUS_FAILED = 'failed';

    public const DELIVERY_STATUS_SENT = 'sent';

    public const DELIVERY_STATUS_NEW = 'new';

    public const DELIVERY_STATUS_SKIPPED = 'skipped';

    public const DELIVERY_STATUS_BOUNCED = 'bounced';

    public const DELIVERY_STATUS_FEEDBACK = 'feedback';

    protected $table = 'campaigns';

    protected $dates = [
        'created_at',
        'updated_at',
        'run_at',
        'delivery_at',
    ];

    /**
     * Get campaign's default mail list.
     */
    public function defaultMailList()
    {
        return $this->belongsTo(CampaignMaillist::class, 'default_maillist_id');
    }

    /**
     * Get campaign's associated mail list.
     */
    public function mailLists()
    {
        return $this->belongsToMany(CampaignMaillist::class, 'campaigns_lists_segments');
    }

    /**
     * Campaign has many campaign links.
     */
    public function campaignLinks()
    {
        return $this->hasMany('Modules\Campaign\Entities\CampaignLink');
    }

    /**
     * Campaign has many campaign webhooks.
     */
    public function campaignWebhooks()
    {
        return $this->hasMany('Modules\Campaign\Entities\CampaignWebhook');
    }

    /**
     * Get campaign's associated tracking domain.
     */
    public function trackingDomain()
    {
        return $this->belongsTo('App\Models\TrackingDomain', 'tracking_domain_id');
    }

    /**
     * Get campaign validation rules.
     */
    public function rules($request = null)
    {
        $rules = [
            'name' => 'required',
            'subject' => 'required',
            'from_email' => 'required|email',
            'from_name' => 'required',
            'reply_to' => 'required|email',
        ];

        if ($this->use_default_sending_server_from_email) {
            $rules['from_email'] = 'nullable|email';
        } else {
            $rules['from_email'] = 'required|email';
        }

        // tracking domain
        if (isset($request) && $request->custom_tracking_domain) {
            $rules['tracking_domain_uid'] = 'required';
        }

        return $rules;
    }

    /**
     * Get campaign tracking logs.
     */
    public function trackingLogs()
    {
        return $this->hasMany('Modules\Campaign\Entities\CampaignTrackingLog');
    }

    public function jobMonitors()
    {
        return $this->hasMany(\Modules\Horizon\Models\JobMonitor::class);
    }

    public function listsSegments()
    {
        return $this->hasMany('Modules\Campaign\Entities\CampaignListsSegment');
    }
}
