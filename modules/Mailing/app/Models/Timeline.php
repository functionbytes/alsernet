<?php

namespace Modules\Mailing\Models;

use Modules\Mailing\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    use HasUid;

    protected $fillable = ['automation2_id', 'subscriber_id', 'auto_trigger_id', 'activity', 'activity_type'];

    /**
     * Associations.
     *
     * @var object | collect
     */
    public function subscriber()
    {
        return $this->belongsTo('Acelle\Model\Subscriber');
    }
}
