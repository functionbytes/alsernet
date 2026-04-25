<?php

namespace Modules\Faqs\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Faqs\Enums\FaqStatus;

class FaqCategory extends Model
{
    use HasFactory;

    protected $table = 'faq_categories';

    protected $fillable = [
        'name',
        'description',
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
        return $this->hasMany(FaqCategoryTranslation::class, 'faq_category_id');
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

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'category_id')->orderBy('order');
    }

    public function publishedFaqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'category_id')
            ->where('status', FaqStatus::PUBLISHED)
            ->orderBy('order');
    }
}
