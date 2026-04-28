<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $campaign_id
 * @property string $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignLink whereCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignLink whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignLink whereUrl($value)
 *
 * @mixin \Eloquent
 */
class CampaignLink extends Model
{
    protected $table = 'campaign_links';
}
