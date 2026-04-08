<?php

namespace Modules\Locales\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Modules\Locales\Models\Locale;

class ThemeTranslationService
{
    private const THEME_LANG_PATH = 'platform/themes/caixilhariablanco/lang';

    /**
     * Returns list of available translation groups.
     *
     * @return array<int, array{name: string, path: string}>
     */
    public function getGroups(): array
    {
        return [
            [
                'name' => 'theme',
                'path' => base_path(self::THEME_LANG_PATH),
            ],
        ];
    }

    /**
     * Returns translations for a given locale and group as key => value pairs.
     *
     * Falls back to es.json if the locale file does not exist yet.
     *
     * @return array<string, string>
     */
    public function getTranslations(string $locale, string $group): array
    {
        $path = $this->resolveFilePath($locale, $group);

        if (! File::exists($path)) {
            $path = $this->resolveFilePath('es', $group);
        }

        if (! File::exists($path)) {
            return [];
        }

        return json_decode(File::get($path), true) ?? [];
    }

    /**
     * Saves translations back to the locale JSON file.
     *
     * @param  array<string, string>  $translations
     */
    public function saveTranslations(string $locale, string $group, array $translations): void
    {
        $path = $this->resolveFilePath($locale, $group);
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function getAvailableLocales(): Collection
    {
        return Locale::active()->get();
    }

    private function resolveFilePath(string $locale, string $group): string
    {
        $groups = collect($this->getGroups())->keyBy('name');
        $groupData = $groups->get($group);

        if (! $groupData) {
            return '';
        }

        return $groupData['path'].'/'.$locale.'.json';
    }
}
