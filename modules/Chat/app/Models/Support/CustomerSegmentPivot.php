<?php

namespace Modules\Chat\Models\Support;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CustomerSegmentPivot extends Pivot
{
    protected $table = 'chat_customer_segment';

    public $timestamps = false;

    protected $fillable = ['customer_id', 'customer_segment_id', 'added_at'];
}
