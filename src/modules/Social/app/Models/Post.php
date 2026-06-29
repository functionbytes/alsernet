<?php

namespace Modules\Social\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Modules\Chat\Models\Accounts\Account;
use Modules\Social\Enums\ApprovalStatus;
use Modules\Social\Enums\PostStatus;
use Modules\Social\Enums\PostType;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity, Searchable, SoftDeletes;

    protected $table = 'social_posts';

    protected $fillable = [
        'account_id',
        'social_account_id',
        'campaign_id',
        'created_by',
        'type',
        'content',
        'media',
        'link_url',
        'link_preview',
        'scheduled_at',
        'published_at',
        'status',
        'external_id',
        'external_url',
        'approval_status',
        'reviewed_by',
        'review_notes',
        'submitted_at',
        'reviewed_at',
        'error_message',
        'publish_results',
        'retry_count',
        'likes_count',
        'comments_count',
        'shares_count',
        'reach',
        'impressions',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'status' => PostStatus::class,
            'approval_status' => ApprovalStatus::class,
            'media' => 'array',
            'link_preview' => 'array',
            'publish_results' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'social_post_label');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(PostVariation::class);
    }

    public function shortUrls(): HasMany
    {
        return $this->hasMany(ShortUrl::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(PostStat::class);
    }

    public function latestStat(): HasMany
    {
        return $this->stats()->latest('fetched_at')->limit(1);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }

    public function getTranslation(string $language): ?PostTranslation
    {
        return $this->translations()->where('language', $language)->first();
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', PostStatus::SCHEDULED);
    }

    public function scopePublished($query)
    {
        return $query->where('status', PostStatus::PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', PostStatus::DRAFT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PostStatus::FAILED);
    }

    public function scopeDueForPublishing($query)
    {
        return $query->scheduled()
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at');
    }

    // Helper Methods
    public function isDraft(): bool
    {
        return $this->status === PostStatus::DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === PostStatus::SCHEDULED;
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::PUBLISHED;
    }

    public function canEdit(): bool
    {
        return in_array($this->status, [PostStatus::DRAFT, PostStatus::SCHEDULED, PostStatus::FAILED]);
    }

    public function getTotalEngagement(): int
    {
        return $this->likes_count + $this->comments_count + $this->shares_count;
    }

    // Spatie Media Library
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useFallbackUrl('/images/placeholder.jpg')
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(300)
                    ->height(300)
                    ->sharpen(10);

                $this->addMediaConversion('facebook')
                    ->width(1200)
                    ->height(630);

                $this->addMediaConversion('instagram')
                    ->width(1080)
                    ->height(1080);

                $this->addMediaConversion('twitter')
                    ->width(1200)
                    ->height(675);

                $this->addMediaConversion('linkedin')
                    ->width(1200)
                    ->height(627);
            });

        $this->addMediaCollection('videos')
            ->useFallbackUrl('/images/video-placeholder.jpg');

        $this->addMediaCollection('documents');
    }

    // Spatie Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['content', 'status', 'scheduled_at', 'approval_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Post {$eventName}");
    }

    // Laravel Scout
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'status' => $this->status->value,
            'campaign' => $this->campaign?->name,
            'network' => $this->socialAccount?->network,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return ! $this->trashed();
    }
}
