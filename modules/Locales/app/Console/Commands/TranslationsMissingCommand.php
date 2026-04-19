<?php

namespace Modules\Locales\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslationsMissingCommand extends Command
{
    protected $signature = 'translations:missing
                            {--module= : Check only a specific module (e.g. Forms)}
                            {--fix : Add missing keys with a placeholder value}';

    protected $description = 'Audit translation files and report missing keys between en/ and es/ directories';

    /** @var array<int, array{file: string, key: string, direction: string}> */
    private array $missing = [];

    public function handle(): int
    {
        $langPaths = $this->resolveLangPaths();

        if (empty($langPaths)) {
            $this->warn('No lang directories found for the given scope.');

            return self::SUCCESS;
        }

        foreach ($langPaths as $langPath) {
            $this->auditDirectory($langPath);
        }

        $this->reportMissing();
        $this->reportHardcodedStrings();

        return self::SUCCESS;
    }

    /** @return string[] */
    private function resolveLangPaths(): array
    {
        $module = $this->option('module');

        if ($module) {
            $path = base_path("modules/{$module}/resources/lang");

            return File::isDirectory($path) ? [$path] : [];
        }

        $paths = [];

        if (File::isDirectory(resource_path('lang'))) {
            $paths[] = resource_path('lang');
        }

        foreach (File::directories(base_path('modules')) as $moduleDir) {
            $langDir = $moduleDir.'/resources/lang';

            if (File::isDirectory($langDir)) {
                $paths[] = $langDir;
            }
        }

        return $paths;
    }

    private function auditDirectory(string $langPath): void
    {
        $enPath = $langPath.'/en';
        $esPath = $langPath.'/es';

        if (! File::isDirectory($enPath) || ! File::isDirectory($esPath)) {
            return;
        }

        foreach (File::files($enPath) as $enFile) {
            $filename = $enFile->getFilename();
            $esFile = $esPath.'/'.$filename;

            $enKeys = $this->flattenKeys(require $enFile->getPathname());
            $esKeys = File::exists($esFile) ? $this->flattenKeys(require $esFile) : [];

            $this->collectMissing($filename, $enKeys, $esKeys, 'en→es', $esFile, $enKeys);
            $this->collectMissing($filename, $esKeys, $enKeys, 'es→en', $esFile, $enKeys);
        }
    }

    /**
     * @param  array<string, string>  $sourceKeys
     * @param  array<string, string>  $targetKeys
     * @param  array<string, string>  $enKeys
     */
    private function collectMissing(
        string $filename,
        array $sourceKeys,
        array $targetKeys,
        string $direction,
        string $esFilePath,
        array $enKeys
    ): void {
        foreach (array_diff_key($sourceKeys, $targetKeys) as $key => $value) {
            $this->missing[] = ['file' => $filename, 'key' => $key, 'direction' => $direction];

            if ($this->option('fix') && $direction === 'en→es') {
                $this->addMissingKey($esFilePath, $key, $enKeys);
            }
        }
    }

    /**
     * @param  array<string, string>  $enKeys  used only when creating a new file
     */
    private function addMissingKey(string $esFilePath, string $key, array $enKeys): void
    {
        $existing = File::exists($esFilePath) ? require $esFilePath : [];

        $parts = explode('.', $key);
        $this->setNestedKey($existing, $parts, '[MISSING TRANSLATION]');

        $content = "<?php\n\nreturn ".$this->exportArray($existing).";\n";
        File::put($esFilePath, $content);
    }

    /**
     * @param  array<string, mixed>  $array
     * @param  string[]  $keys
     */
    private function setNestedKey(array &$array, array $keys, string $value): void
    {
        $key = array_shift($keys);

        if (empty($keys)) {
            $array[$key] = $value;

            return;
        }

        if (! isset($array[$key]) || ! is_array($array[$key])) {
            $array[$key] = [];
        }

        $this->setNestedKey($array[$key], $keys, $value);
    }

    private function reportMissing(): void
    {
        if (empty($this->missing)) {
            $this->info('No missing translation keys found.');

            return;
        }

        $this->warn(count($this->missing).' missing translation key(s) found:');
        $this->table(['File', 'Missing Key', 'Direction'], $this->missing);

        if ($this->option('fix')) {
            $this->info('Missing en→es keys have been added with [MISSING TRANSLATION] placeholder.');
        }
    }

    private function reportHardcodedStrings(): void
    {
        $this->newLine();
        $this->info('Scanning Blade files for hardcoded Spanish strings...');

        $viewPaths = $this->resolveViewPaths();
        $found = [];

        foreach ($viewPaths as $viewPath) {
            $this->scanBladeFiles($viewPath, $found);
        }

        if (empty($found)) {
            $this->info('No hardcoded Spanish strings detected.');

            return;
        }

        $this->warn(count($found).' potential hardcoded string(s) found:');
        $this->table(['File', 'Line', 'Content'], $found);
    }

    /** @return string[] */
    private function resolveViewPaths(): array
    {
        $module = $this->option('module');

        if ($module) {
            $path = base_path("modules/{$module}/resources/views");

            return File::isDirectory($path) ? [$path] : [];
        }

        $paths = [];

        if (File::isDirectory(resource_path('views'))) {
            $paths[] = resource_path('views');
        }

        foreach (File::directories(base_path('modules')) as $moduleDir) {
            $viewDir = $moduleDir.'/resources/views';

            if (File::isDirectory($viewDir)) {
                $paths[] = $viewDir;
            }
        }

        return $paths;
    }

    /**
     * @param  array<int, array{file: string, line: int, content: string}>  $found
     */
    private function scanBladeFiles(string $viewPath, array &$found): void
    {
        $bladeFiles = File::allFiles($viewPath);

        foreach ($bladeFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $lines = explode("\n", File::get($file->getPathname()));
            $relativePath = str_replace(base_path().'/', '', $file->getPathname());

            foreach ($lines as $lineNumber => $line) {
                if ($this->hasHardcodedSpanish($line)) {
                    $found[] = [
                        'file' => $relativePath,
                        'line' => $lineNumber + 1,
                        'content' => trim(substr($line, 0, 100)),
                    ];
                }
            }
        }
    }

    private function hasHardcodedSpanish(string $line): bool
    {
        // Skip lines that are pure Blade/PHP directives or already wrapped in __()
        if (preg_match('/\{\{\s*__\s*\(/', $line)) {
            return false;
        }

        // Look for Spanish accented characters in HTML text nodes (not inside PHP/Blade tags)
        return (bool) preg_match('/>[^<{]*[áéíóúüñÁÉÍÓÚÜÑ][^<{]*</', $line);
    }

    /**
     * Flatten a nested array to dot-notation keys.
     *
     * @param  array<string, mixed>  $array
     * @return array<string, string>
     */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : (string) $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenKeys($value, $fullKey));
            } else {
                $result[$fullKey] = (string) $value;
            }
        }

        return $result;
    }

    /**
     * Export a PHP array as a formatted string.
     *
     * @param  array<string, mixed>  $array
     */
    private function exportArray(array $array, int $indent = 1): string
    {
        $pad = str_repeat('    ', $indent);
        $closePad = str_repeat('    ', $indent - 1);
        $lines = ['['];

        foreach ($array as $key => $value) {
            $formattedKey = "'".addslashes((string) $key)."'";

            if (is_array($value)) {
                $lines[] = "{$pad}{$formattedKey} => ".$this->exportArray($value, $indent + 1).',';
            } else {
                $formattedValue = "'".addslashes((string) $value)."'";
                $lines[] = "{$pad}{$formattedKey} => {$formattedValue},";
            }
        }

        $lines[] = "{$closePad}]";

        return implode("\n", $lines);
    }
}
