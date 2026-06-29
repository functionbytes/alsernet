<?php

namespace Modules\HelpdeskCampaigns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignVariant extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_campaign_variants';

    protected $fillable = [
        'campaign_id',
        'label',
        'weight',
        'content',
        'appearance',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'appearance' => 'array',
            'weight' => 'integer',
            'impressions_count' => 'integer',
            'clicks_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getCtrAttribute(): float
    {
        if ($this->impressions_count === 0) {
            return 0.0;
        }

        return round(($this->clicks_count / $this->impressions_count) * 100, 2);
    }
}
