<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Reviews\Enums\ReviewRating;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Review extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'reviews';

    protected $fillable = [
        'location_id',
        'google_review_id',
        'reviewer_name',
        'reviewer_photo_url',
        'star_rating',
        'comment',
        'review_time',
        'update_time',
        'google_reply_text',
        'google_reply_time',
        'raw_json',
        'synced_at',
    ];

    protected $appends = [
        'location_name',
        'reply_status',
        'is_visible',
        'actions',
    ];

    protected function casts(): array
    {
        return [
            'star_rating' => ReviewRating::class,
            'review_time' => 'datetime',
            'update_time' => 'datetime',
            'google_reply_time' => 'datetime',
            'raw_json' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['star_rating', 'comment'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ReviewGoogleLocation::class, 'location_id');
    }

    public function moderation(): HasOne
    {
        return $this->hasOne(ReviewModeration::class, 'review_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ReviewReply::class, 'review_id');
    }

    public function scopeRating($query, ReviewRating|string $rating)
    {
        return $query->where('star_rating', $rating);
    }

    public function scopeWithComment($query)
    {
        return $query->whereNotNull('comment');
    }

    public function scopeWithoutComment($query)
    {
        return $query->whereNull('comment');
    }

    public function scopeWithGoogleReply($query)
    {
        return $query->whereNotNull('google_reply_text');
    }

    public function scopeWithoutGoogleReply($query)
    {
        return $query->whereNull('google_reply_text');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('review_time', '>=', now()->subDays($days));
    }

    public function scopeVisible($query)
    {
        return $query->whereHas('moderation', function ($q) {
            $q->where('is_visible', true);
        });
    }

    public function scopeFeatured($query)
    {
        return $query->whereHas('moderation', function ($q) {
            $q->where('is_featured', true);
        });
    }

    public function isVisible(): bool
    {
        return $this->moderation?->is_visible ?? config('reviews.general.default_moderation_visible', true);
    }

    public function isFeatured(): bool
    {
        return $this->moderation?->is_featured ?? false;
    }

    public function hasGoogleReply(): bool
    {
        return ! is_null($this->google_reply_text);
    }

    public function getStarRatingValueAttribute(): int
    {
        return $this->star_rating->value();
    }

    public function getLocationNameAttribute(): ?string
    {
        return $this->location?->name;
    }

    public function getReplyStatusAttribute(): string
    {
        if ($this->hasGoogleReply()) {
            return 'published';
        }

        $lastReply = $this->replies()->latest()->first();

        if (! $lastReply) {
            return 'unanswered';
        }

        return $lastReply->status?->value ?? 'unanswered';
    }

    public function getIsVisibleAttribute(): bool
    {
        return $this->isVisible();
    }

    public function getActionsAttribute(): string
    {
        $reviewId = $this->id;
        $hasReply = $this->hasGoogleReply();

        $replyButton = $hasReply
            ? ''
            : '<button class="btn btn-sm btn-primary btn-reply" data-review-id="'.$reviewId.'" title="Responder">
                    <i class="fas fa-reply"></i>
                </button>';

        return <<<HTML
            <div class="d-flex gap-1 justify-content-center">
                {$replyButton}
                <a href="/reviews/{$reviewId}" class="btn btn-sm btn-light" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </a>
            </div>
        HTML;
    }
}
