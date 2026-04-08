<?php

namespace Modules\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Blog\Enums\TranslationStatus;

class BlogPostTranslation extends Model
{
    protected $table = 'blog_post_translations';

    protected $fillable = [
        'blog_post_id',
        'locale',
        'title',
        'slug',
        'description',
        'content',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'status',
        'translated_at',
        'source_hash',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TranslationStatus::class,
            'translated_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get translation logs for this specific locale.
     */
    public function getLogsAttribute(): Collection
    {
        return BlogTranslationLog::query()
            ->where('blog_post_id', $this->blog_post_id)
            ->where('locale', $this->locale)
            ->latest()
            ->get();
    }

    public function isComplete(): bool
    {
        return ! empty($this->title)
            && ! empty($this->content)
            && ! empty($this->seo_title)
            && ! empty($this->seo_description);
    }

    public function isPartial(): bool
    {
        return (! empty($this->title) || ! empty($this->content))
            && ! $this->isComplete();
    }

    public function isEmpty(): bool
    {
        return empty($this->title) && empty($this->content);
    }
}
