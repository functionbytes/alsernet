<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;

class CampaignListsSegment extends Model
{

    protected $table = "campaigns_lists_segments";

    public function campaign()
    {
        return $this->belongsTo('App\Models\Campaign\Campaign');
    }

    public function mailList()
    {
        return $this->belongsTo('App\Models\Campaign\CampaignMaillist');
    }

    public function segment()
    {
        return $this->belongsTo('App\Models\Campaign\CampaignSegment');
    }

}
