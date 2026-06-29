<?php

/**
 * SegmentCondition class.
 *
 * Model class for segment filter options
 *
 * LICENSE: This product components software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   MVC Model
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace Modules\Campaign\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uid
 * @property int $segment_id
 * @property int|null $field_id
 * @property string $operator
 * @property string $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CampaignField|null $field
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition whereFieldId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition whereOperator($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition whereSegmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignSegmentCondition whereValue($value)
 *
 * @mixin \Eloquent
 */
class CampaignSegmentCondition extends Model
{
    use HasUid;

    protected $table = 'campaign_segment_conditions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'segment_id', 'field', 'field_id', 'operator', 'value', 'order',
    ];

    public function field()
    {
        return $this->belongsTo('Modules\Campaign\Models\CampaignField');
    }
}
