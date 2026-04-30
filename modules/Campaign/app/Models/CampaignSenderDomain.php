<?php

namespace Modules\Campaign\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignSenderDomain extends Model
{
    use HasFactory;
    use HasUid;

    protected $table = 'campaign_sender_domains';

    protected $fillable = [
        'uid',
        'domain',
        'spf_valid',
        'dmarc_valid',
        'dkim_valid',
        'mx_valid',
        'score',
        'status',
        'verified_at',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'spf_valid' => 'boolean',
            'dmarc_valid' => 'boolean',
            'dkim_valid' => 'boolean',
            'mx_valid' => 'boolean',
            'verified_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }
}
