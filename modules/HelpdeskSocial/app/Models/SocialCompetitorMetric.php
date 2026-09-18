<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialCompetitorMetric extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_competitor_metrics';

    protected $fillable = [
        'social_competitor_id',
        'metric_type',
        'value',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'captured_at' => 'datetime',
        ];
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(SocialCompetitor::class, 'social_competitor_id');
    }
}
