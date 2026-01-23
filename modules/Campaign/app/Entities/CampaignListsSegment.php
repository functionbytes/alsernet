<?php

namespace Modules\Campaign\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $maillist_id
 * @property int|null $segment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Modules\Campaign\Entities\Campaign $campaign
 * @property-read \Modules\Campaign\Entities\CampaignMaillist|null $mailList
 * @property-read \Modules\Campaign\Entities\CampaignSegment|null $segment
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignListsSegment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignListsSegment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignListsSegment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignListsSegment whereCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignListsSegment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignListsSegment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignListsSegment whereMaillistId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignListsSegment whereSegmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignListsSegment whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CampaignListsSegment extends Model
{
    protected $table = 'campaigns_lists_segments';

    public function campaign()
    {
        return $this->belongsTo('Modules\Campaign\Entities\Campaign');
    }

    public function mailList()
    {
        return $this->belongsTo('Modules\Campaign\Entities\CampaignMaillist');
    }

    public function segment()
    {
        return $this->belongsTo('Modules\Campaign\Entities\CampaignSegment');
    }
}
