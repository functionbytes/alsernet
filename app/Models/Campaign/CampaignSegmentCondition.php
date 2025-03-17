<?php

/**
 * SegmentCondition class.
 *
 * Model class for segment filter options
 *
 * LICENSE: This product includes software developed at
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

namespace App\Models\Campaign;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

class CampaignSegmentCondition extends Model
{
    use HasUid;

    protected $table = "campaigns_maillists_segment_conditions";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'field_id', 'operator', 'value',
    ];

    public function field()
    {
        return $this->belongsTo('App\Models\Campaign\CampaignField');
    }
}
