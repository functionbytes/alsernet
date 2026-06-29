<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignBlacklist extends Model
{
    use HasFactory;

    protected $table = 'campaign_sending_server_blacklists';

    protected $fillable = [
        'email',
        'reason',
        'source',
    ];
}
