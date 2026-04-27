<?php

namespace Modules\Ecommerce\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Ecommerce\Models\Translation;

trait HasTranslations
{
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function translate(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $fallback = config('app.fallback_locale', 'es');

        if ($locale === $fallback) {
            return $this->{$field};
        }

        $translation = $this->translations
            ->where('locale', $locale)
            ->where('field', $field)
            ->first();

        return $translation?->value ?: $this->{$field};
    }

    public function setTranslation(string $field, string $locale, string $value): void
    {
        $this->translations()->updateOrCreate(
            ['locale' => $locale, 'field' => $field],
            ['value' => $value]
        );
    }
}
