<?php

namespace Modules\Faqs\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Faqs\Enums\FaqStatus;

class Faq extends Model
{
    use HasFactory;

    protected $table = 'faqs';

    protected $fillable = [
        'question',
        'answer',
        'category_id',
        'order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => FaqStatus::class,
            'order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', FaqStatus::PUBLISHED);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(FaqTranslation::class, 'faq_id');
    }

    /**
     * Get a translated field value with fallback to the base field.
     */
    public function trans(string $field, ?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();

        $translation = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();

        if ($translation && filled($translation->$field)) {
            return $translation->$field;
        }

        return $this->$field ?? null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'category_id')->withDefault();
    }
}
