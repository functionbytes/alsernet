<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $maillist_id
 * @property int|null $segment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Campaign $campaign
 * @property-read CampaignMaillist|null $mailList
 * @property-read CampaignSegment|null $segment
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
    protected $table = 'campaign_lists_segments';

    public function campaign()
    {
        return $this->belongsTo('Modules\Campaign\Models\Campaign');
    }

    public function mailList()
    {
        return $this->belongsTo('Modules\Campaign\Models\CampaignMaillist');
    }

    public function segment()
    {
        return $this->belongsTo('Modules\Campaign\Models\CampaignSegment');
    }
}
