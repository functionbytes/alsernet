<?php

namespace Modules\Helpdesk\Models\Campaigns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripStep extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_drip_steps';

    protected $fillable = [
        'campaign_id',
        'step_order',
        'delay_minutes',
        'channel',
        'template_type',
        'body',
        'template_id',
        'template_params',
    ];

    protected function casts(): array
    {
        return [
            'template_params' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DripCampaign::class, 'campaign_id');
    }
}
