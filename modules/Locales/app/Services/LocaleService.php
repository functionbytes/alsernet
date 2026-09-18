<?php

namespace Modules\Locales\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Locales\Models\Locale;

class LocaleService
{
    private const CACHE_KEY = 'locales.active';

    private const CACHE_TTL = 3600;

    public function getActiveLocales(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => Locale::active()->get());
    }

    public function getActiveCodes(): array
    {
        return $this->getActiveLocales()->pluck('code')->toArray();
    }

    public function getDefault(): ?Locale
    {
        return $this->getActiveLocales()->firstWhere('is_default', true)
            ?? Locale::getDefault();
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
