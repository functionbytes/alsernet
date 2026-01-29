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

namespace Modules\Mailing\Models;

use Modules\Mailing\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

class SegmentCondition extends Model
{
    use HasUid;

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
        return $this->belongsTo('Acelle\Model\Field');
    }
}
