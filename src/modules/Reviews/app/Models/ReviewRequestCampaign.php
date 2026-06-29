<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewRequestCampaign extends Model
{
    protected $table = 'review_request_campaigns';

    protected $fillable = [
        'location_id',
        'name',
        'subject',
        'message',
        'review_url',
        'sent_count',
        'status',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_count' => 'integer',
            'scheduled_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('scheduled_at')->where('status', 'draft');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ReviewGoogleLocation::class, 'location_id');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(ReviewRequestSend::class, 'campaign_id');
    }

    public function getReviewUrl(): string
    {
        return $this->review_url;
    }
}
