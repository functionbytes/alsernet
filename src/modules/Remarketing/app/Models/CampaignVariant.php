<?php

namespace Modules\Remarketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignVariant extends Model
{
    use HasFactory;

    protected $table = 'remarketing_campaign_variants';

    protected $fillable = [
        'campaign_id',
        'name',
        'subject',
        'preheader',
        'weight',
        'template_id',
        'sent',
        'opened',
        'clicked',
        'revenue',
        'is_winner',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'sent' => 'integer',
            'opened' => 'integer',
            'clicked' => 'integer',
            'revenue' => 'decimal:2',
            'is_winner' => 'boolean',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function openRate(): float
    {
        return $this->sent > 0 ? round(($this->opened / $this->sent) * 100, 2) : 0.0;
    }

    public function ctr(): float
    {
        return $this->sent > 0 ? round(($this->clicked / $this->sent) * 100, 2) : 0.0;
    }
}
