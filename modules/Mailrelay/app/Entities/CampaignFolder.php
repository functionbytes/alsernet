<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignFolder extends Model
{
    use HasFactory;

    protected $table = 'mails_campaign_folders';

    protected $fillable = [
        'name',
    ];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
