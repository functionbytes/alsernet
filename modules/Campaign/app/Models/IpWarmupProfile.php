<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;

class IpWarmupProfile extends Model
{
    protected $table = 'campaign_ip_warmup_profiles';

    protected $fillable = [
        'sending_server_id',
        'day_number',
        'daily_limit',
    ];
}
