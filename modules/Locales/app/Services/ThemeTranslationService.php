<?php

namespace Modules\Locales\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Modules\Locales\Models\Locale;

class ThemeTranslationService
{
    private const THEME_LANG_PATH = 'platform/themes/caixilhariablanco/lang';

    private const MODULES_STATUSES = 'modules_statuses.json';

    /**
     * Returns list of available translation groups.
     *
     * Each group represents one editable file:
     * - Theme: one group "theme" for platform/themes/.../lang/{locale}.json
     * - Modules: one group per PHP file under modules/{Name}/lang or modules/{Name}/resources/lang
     *
     * @return array<int, array{name: string, module: string, module_label: string, file: string, format: string, path: string}>
     */
    public function getGroups(): array
    {
        return Cache::remember('locales.translation_groups', 3600, function () {
            $groups = [];

            $themePath = base_path(self::THEME_LANG_PATH);
            if (File::isDirectory($themePath)) {
                $groups[] = [
                    'name' => 'theme',
                    'module' => 'theme',
                    'module_label' => 'Tema',
                    'file' => 'theme',
                    'format' => 'json',
                    'path' => $themePath,
                ];
            }

            foreach ($this->getActiveModules() as $module) {
                foreach ($this->getModuleLangDirs($module) as $langDir) {
                    foreach ($this->getModuleFiles($langDir) as $file) {
                        $groups[] = [
                            'name' => $this->buildGroupName($module, $file),
                            'module' => $module,
                            'module_label' => $module,
                            'file' => $file,
                            'format' => 'php',
                            'path' => $langDir,
                        ];
                    }
                }
            }

            return $groups;
        });
    }

    /**
     * Returns translations for a given locale and group as flat key => value pairs.
     *
     * PHP files are flattened to dot notation. Falls back to source locale if file missing.
     *
     * @return array<string, string>
     */
    public function getTranslations(string $locale, string $group): array
    {
        $groupData = $this->findGroup($group);

        if (! $groupData) {
            return [];
        }

        $path = $this->resolveFilePath($locale, $groupData);

        if (! File::exists($path)) {
            $fallback = $locale === 'es' ? 'en' : 'es';
            $path = $this->resolveFilePath($fallback, $groupData);
        }

        if (! File::exists($path)) {
            return [];
        }

        if ($groupData['format'] === 'json') {
            return json_decode(File::get($path), true) ?? [];
        }

        $data = require $path;

        return is_array($data) ? Arr::dot($data) : [];
    }

    /**
     * Saves translations back to the locale file in its native format.
     *
     * @param  array<string, string>  $translations
     */
    public function saveTranslations(string $locale, string $group, array $translations): void
    {
        $groupData = $this->findGroup($group);

        if (! $groupData) {
            return;
        }

        $path = $this->resolveFilePath($locale, $groupData);
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if ($groupData['format'] === 'json') {
            File::put($path, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return;
        }

        File::put($path, $this->renderPhpArray(Arr::undot($translations)));
    }

    public function getAvailableLocales(): Collection
    {
        return Locale::active()->get();
    }

    /**
     * Groups returned by getGroups() organized by module for UI display.
     *
     * @return Collection<string, array{label: string, groups: array<int, array<string, string>>}>
     */
    public function getGroupsByModule(): Collection
    {
        return collect($this->getGroups())
            ->groupBy('module')
            ->map(fn (Collection $items, string $module) => [
                'label' => $items->first()['module_label'] ?? $module,
                'groups' => $items->values()->all(),
            ]);
    }

    private function findGroup(string $name): ?array
    {
        return collect($this->getGroups())->firstWhere('name', $name);
    }

    private function resolveFilePath(string $locale, array $groupData): string
    {
        if ($groupData['format'] === 'json') {
            return $groupData['path'].'/'.$locale.'.json';
        }

        return $groupData['path'].'/'.$locale.'/'.$groupData['file'].'.php';
    }

    private function buildGroupName(string $module, string $file): string
    {
        return strtolower($module).'.'.$file;
    }

    /**
     * @return array<int, string>
     */
    private function getActiveModules(): array
    {
        $path = base_path(self::MODULES_STATUSES);

        if (! File::exists($path)) {
            return [];
        }

        $statuses = json_decode(File::get($path), true) ?? [];

        return array_keys(array_filter($statuses));
    }

    /**
     * @return array<int, string>
     */
    private function getModuleLangDirs(string $module): array
    {
        $dirs = [];

        foreach (['lang', 'resources/lang'] as $candidate) {
            $path = base_path("modules/{$module}/{$candidate}");

            if (File::isDirectory($path)) {
                $dirs[] = $path;
            }
        }

        return $dirs;
    }

    /**
     * Returns unique file basenames (without .php) present under the lang dir's locale subfolders.
     *
     * @return array<int, string>
     */
    private function getModuleFiles(string $langDir): array
    {
        $files = [];

        foreach (File::directories($langDir) as $localeDir) {
            foreach (File::files($localeDir) as $file) {
                if ($file->getExtension() === 'php') {
                    $files[$file->getFilenameWithoutExtension()] = true;
                }
            }
        }

        return array_keys($files);
    }

    private function renderPhpArray(array $data): string
    {
        return "<?php\n\nreturn ".$this->exportArray($data, 0).";\n";
    }

    private function exportArray(array $data, int $depth): string
    {
        if (empty($data)) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth + 1);
        $closingIndent = str_repeat('    ', $depth);
        $isList = array_is_list($data);
        $lines = [];

        foreach ($data as $key => $value) {
            $rendered = is_array($value)
                ? $this->exportArray($value, $depth + 1)
                : $this->exportScalar($value);

            $lines[] = $isList
                ? $indent.$rendered
                : $indent.$this->exportScalar($key).' => '.$rendered;
        }

        return "[\n".implode(",\n", $lines).",\n".$closingIndent.']';
    }

    private function exportScalar(mixed $value): string
    {
        if (is_string($value)) {
            return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $value)."'";
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        return (string) $value;
    }
}
