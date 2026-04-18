<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Modules\Reviews\Database\Factories\ReviewFactory;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Enums\ReviewSource;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Review extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'reviews';

    protected static function newFactory()
    {
        return ReviewFactory::new();
    }

    protected static function booted(): void
    {
        $flush = static function (): void {
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['reviews-dashboard'])->flush();
            }
        };

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }

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
        'source',
        'raw_json',
        'synced_at',
        'sla_alerted_at',
        'translated_comment',
        'detected_language',
        'translation_cached_at',
    ];

    protected $appends = [
        'location_name',
        'reply_status',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'star_rating' => ReviewRating::class,
            'source' => ReviewSource::class,
            'review_time' => 'datetime',
            'update_time' => 'datetime',
            'google_reply_time' => 'datetime',
            'raw_json' => 'array',
            'synced_at' => 'datetime',
            'sla_alerted_at' => 'datetime',
            'translation_cached_at' => 'datetime',
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

    public function translations(): HasMany
    {
        return $this->hasMany(ReviewTranslation::class, 'review_id');
    }

    public function reply(): HasOne
    {
        return $this->hasOne(ReviewReply::class, 'review_id')->latestOfMany();
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

    /**
     * Get the numeric star rating value.
     *
     * Note: This accessor is intentionally kept for API responses and backwards compatibility.
     * It automatically exposes star_rating as an integer (1-5) instead of the enum value
     * when the model is serialized to JSON/array, making it easier for frontend consumption.
     *
     * @return int Star rating value (1-5)
     */
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

        $lastReply = $this->relationLoaded('reply') ? $this->reply : $this->replies()->latest()->first();

        if (! $lastReply) {
            return 'unanswered';
        }

        return $lastReply->status?->value ?? 'unanswered';
    }

    public function getIsVisibleAttribute(): bool
    {
        return $this->isVisible();
    }
}
