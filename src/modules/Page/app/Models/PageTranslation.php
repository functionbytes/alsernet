<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Locales\Models\Locale;
use Modules\Page\Enums\PageStatus;

class PageTranslation extends Model
{
    protected $table = 'page_translations';

    protected $appends = ['locale'];

    protected $fillable = [
        'page_id',
        'locale_id',
        'title',
        'slug',
        'content',
        'description',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'seo_image_url',
        'seo_noindex',
        'status',
        'published_at',
    ];

    protected $casts = [
        'seo_noindex' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function localeModel(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale_id');
    }

    public function getLocaleAttribute(): ?string
    {
        return $this->localeModel?->code;
    }

    public function scopeForLocale(Builder $query, string $code): void
    {
        $query->whereHas('localeModel', fn ($q) => $q->where('code', $code));
    }

    public function scopeNotLocale(Builder $query, string $code): void
    {
        $query->whereDoesntHave('localeModel', fn ($q) => $q->where('code', $code));
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published->value
            && $this->published_at !== null
            && $this->published_at->isPast();
    }
}
