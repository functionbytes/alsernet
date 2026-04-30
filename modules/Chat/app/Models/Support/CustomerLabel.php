<?php

namespace Modules\Chat\Models\Support;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CustomerLabel extends Pivot
{
    protected $table = 'chat_customer_label';

    public $timestamps = false;

    protected $fillable = ['customer_id', 'label_id'];
}
