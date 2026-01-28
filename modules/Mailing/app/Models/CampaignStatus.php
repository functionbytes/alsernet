<?php

namespace Modules\Mailing\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignStatus extends Model
{
    use HasFactory;

    protected $table = 'mails_campaign_statuses';

    protected $fillable = [
        'campaign_id',
        'status',
        'changed_at',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
