<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostStat extends Model
{
    use HasFactory;

    protected $table = 'social_post_stats';

    protected $fillable = [
        'post_id',
        'likes',
        'comments',
        'shares',
        'views',
        'reach',
        'impressions',
        'clicks',
        'engagement_rate',
        'ctr',
        'raw_data',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'fetched_at' => 'datetime',
            'engagement_rate' => 'decimal:2',
            'ctr' => 'decimal:2',
        ];
    }

    // Relationships
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // Helper Methods
    public function getTotalEngagement(): int
    {
        return $this->likes + $this->comments + $this->shares;
    }

    public function calculateEngagementRate(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        return round(($this->getTotalEngagement() / $this->impressions) * 100, 2);
    }

    public function calculateCTR(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        return round(($this->clicks / $this->impressions) * 100, 2);
    }
}
