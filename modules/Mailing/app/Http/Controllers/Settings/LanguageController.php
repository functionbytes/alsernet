<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;

class LanguageController extends Controller
{
    use ValidatesRequests;

    protected string $languagesPath = 'resources/lang';

    protected array $supportedFormats = ['json', 'php'];

    public function __construct()
    {
        $this->languagesPath = base_path('resources/lang');
    }

    /**
     * Display a listing of all languages and their translation progress.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.languages.view');

        $languages = $this->getAvailableLanguages();
        $translationStats = $this->getTranslationStats($languages);

        // Prepare stats array for view
        $stats = [
            'total' => count($languages),
            'active' => count(array_filter($languages, fn($l) => $l['is_active'] ?? false)),
            'last_updated' => now(),
        ];

        // Get default language if set
        $defaultLanguage = null;

        return view('mailing::settings.languages.index', [
            'languages' => $languages,
            'translationStats' => $translationStats,
            'stats' => $stats,
            'defaultLanguage' => $defaultLanguage,
        ]);
    }

    /**
     * Show the form for creating a new language.
     */
    public function create(): View
    {
        Gate::authorize('mailing.settings.languages.create');

        $commonLanguages = $this->getCommonLanguages();
        $existingLanguages = $this->getAvailableLanguages();

        return view('mailing::settings.languages.create', [
            'commonLanguages' => $commonLanguages,
            'existingLanguages' => $existingLanguages,
        ]);
    }

    /**
     * Store a newly created language.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.languages.create');

        try {
            $validated = $this->validate($request, [
                'locale' => 'required|string|regex:/^[a-z]{2}(_[A-Z]{2})?$/|max:5',
                'name' => 'required|string|max:255',
                'native_name' => 'nullable|string|max:255',
                'direction' => 'in:ltr,rtl',
                'copy_from' => 'nullable|string|exists_language',
            ]);

            $locale = $validated['locale'];
            $languagePath = $this->languagesPath.'/'.$locale;

            // Create language directory
            if (! File::isDirectory($languagePath)) {
                File::makeDirectory($languagePath, 0755, true);
            }

            // Copy translations from existing language if specified
            if (! empty($validated['copy_from'])) {
                $sourcePath = $this->languagesPath.'/'.$validated['copy_from'];
                if (File::isDirectory($sourcePath)) {
                    $this->copyTranslationFiles($sourcePath, $languagePath);
                }
            }

            // Store language metadata
            $this->saveLanguageMetadata($locale, [
                'name' => $validated['name'],
                'native_name' => $validated['native_name'] ?? $validated['name'],
                'direction' => $validated['direction'] ?? 'ltr',
                'created_at' => now()->toIso8601String(),
            ]);

            return redirect()
                ->route('settings.mailing.templates.languages.index')
                ->with('success', "Idioma '{$validated['name']}' creado correctamente.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el idioma: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified language.
     */
    public function edit(string $id): View
    {
        Gate::authorize('mailing.settings.languages.edit');

        $language = $this->getLanguageMetadata($id);

        if (! $language) {
            abort(404, 'Language not found');
        }

        return view('mailing::settings.languages.edit', [
            'language' => $language,
            'id' => $id,
        ]);
    }

    /**
     * Update the specified language metadata.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.languages.edit');

        try {
            $language = $this->getLanguageMetadata($id);

            if (! $language) {
                abort(404, 'Language not found');
            }

            $validated = $this->validate($request, [
                'name' => 'required|string|max:255',
                'native_name' => 'nullable|string|max:255',
                'direction' => 'in:ltr,rtl',
            ]);

            $this->saveLanguageMetadata($id, array_merge($language, [
                'name' => $validated['name'],
                'native_name' => $validated['native_name'] ?? $language['native_name'] ?? $validated['name'],
                'direction' => $validated['direction'] ?? $language['direction'] ?? 'ltr',
            ]));

            return redirect()
                ->route('settings.mailing.templates.languages.index')
                ->with('success', "Idioma '{$validated['name']}' actualizado correctamente.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el idioma: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified language and its translations.
     */
    public function destroy(string $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.languages.delete');

        try {
            $defaultLanguage = config('app.locale', 'en');

            if ($id === $defaultLanguage) {
                return redirect()
                    ->back()
                    ->with('error', 'No se puede eliminar el idioma predeterminado.');
            }

            $languagePath = $this->languagesPath.'/'.$id;

            if (File::isDirectory($languagePath)) {
                File::deleteDirectory($languagePath);
            }

            // Remove metadata
            $metadataPath = $this->languagesPath.'/languages.json';
            if (File::exists($metadataPath)) {
                $languages = json_decode(File::get($metadataPath), true) ?? [];
                unset($languages[$id]);
                File::put($metadataPath, json_encode($languages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return redirect()
                ->route('settings.mailing.templates.languages.index')
                ->with('success', 'Idioma eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar el idioma: '.$e->getMessage());
        }
    }

    /**
     * Set the specified language as the default application language.
     */
    public function setDefault(string $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.languages.edit');

        try {
            $language = $this->getLanguageMetadata($id);

            if (! $language) {
                abort(404, 'Language not found');
            }

            // Update config
            $envPath = base_path('.env');
            $envContent = File::get($envPath);

            if (preg_match('/^APP_LOCALE=(.*)$/m', $envContent)) {
                $envContent = preg_replace(
                    '/^APP_LOCALE=.*$/m',
                    'APP_LOCALE='.$id,
                    $envContent
                );
            } else {
                $envContent .= "\nAPP_LOCALE={$id}\n";
            }

            File::put($envPath, $envContent);

            // Clear cache
            if (File::exists(base_path('bootstrap/cache/config.php'))) {
                File::delete(base_path('bootstrap/cache/config.php'));
            }

            return redirect()
                ->route('settings.mailing.templates.languages.index')
                ->with('success', "Idioma predeterminado establecido a '{$language['name']}'.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al establecer el idioma predeterminado: '.$e->getMessage());
        }
    }

    /**
     * Download language translation files as a zip archive.
     */
    public function download(string $id): Response
    {
        Gate::authorize('mailing.settings.languages.view');

        try {
            $language = $this->getLanguageMetadata($id);

            if (! $language) {
                abort(404, 'Language not found');
            }

            $languagePath = $this->languagesPath.'/'.$id;

            if (! File::isDirectory($languagePath)) {
                abort(404, 'Language files not found');
            }

            $zipPath = storage_path('app/temp/'.$id.'_'.now()->timestamp.'.zip');
            File::makeDirectory(dirname($zipPath), 0755, true, true);

            $zip = new \ZipArchive;
            $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            $files = File::allFiles($languagePath);
            foreach ($files as $file) {
                $relativePath = $file->getRelativePathname();
                $zip->addFile($file->getPathname(), $relativePath);
            }

            $zip->close();

            return response()->download($zipPath, "{$id}_translations_".now()->format('Y-m-d_H-i-s').'.zip')
                ->deleteFileAfterSend();
        } catch (\Exception $e) {
            abort(500, 'Error al descargar archivo: '.$e->getMessage());
        }
    }

    /**
     * Import translation files from uploaded archive.
     */
    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.languages.create');

        try {
            $validated = $this->validate($request, [
                'language_id' => 'required|string|regex:/^[a-z]{2}(_[A-Z]{2})?$/',
                'translation_file' => 'required|file|mimes:zip|max:10240',
                'merge' => 'boolean',
            ]);

            $languageId = $validated['language_id'];
            $languagePath = $this->languagesPath.'/'.$languageId;
            $merge = $validated['merge'] ?? false;

            // Create language directory if it doesn't exist
            if (! File::isDirectory($languagePath)) {
                File::makeDirectory($languagePath, 0755, true);
            } elseif (! $merge) {
                // Clear existing files if not merging
                File::deleteDirectory($languagePath);
                File::makeDirectory($languagePath, 0755, true);
            }

            // Extract uploaded zip file
            $uploadedFile = $request->file('translation_file');
            $tempPath = storage_path('app/temp/import_'.uniqid());
            File::makeDirectory($tempPath, 0755, true);

            $zip = new \ZipArchive;
            if ($zip->open($uploadedFile->getPathname()) !== true) {
                throw new \Exception('Unable to open zip file');
            }

            $zip->extractTo($tempPath);
            $zip->close();

            // Move files to language directory
            $tempLanguageDir = $this->findLanguageDirectory($tempPath);

            if (! $tempLanguageDir) {
                throw new \Exception('Invalid archive structure - no translation files found');
            }

            $this->copyTranslationFiles($tempLanguageDir, $languagePath);

            // Cleanup
            File::deleteDirectory($tempPath);

            // Save metadata if new language
            if (! $this->getLanguageMetadata($languageId)) {
                $this->saveLanguageMetadata($languageId, [
                    'name' => $languageId,
                    'native_name' => $languageId,
                    'direction' => 'ltr',
                    'created_at' => now()->toIso8601String(),
                ]);
            }

            $message = $merge ? 'Traducciones fusionadas exitosamente.' : 'Traducciones importadas exitosamente.';

            return redirect()
                ->route('settings.mailing.templates.languages.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al importar traducciones: '.$e->getMessage());
        }
    }

    /**
     * Get available languages and their information.
     */
    protected function getAvailableLanguages(): array
    {
        $languages = [];
        $metadataPath = $this->languagesPath.'/languages.json';
        $metadata = [];

        if (File::exists($metadataPath)) {
            $metadata = json_decode(File::get($metadataPath), true) ?? [];
        }

        $directories = File::directories($this->languagesPath);

        foreach ($directories as $dir) {
            $locale = basename($dir);
            if ($locale !== '.' && $locale !== '..' && ! in_array($locale, ['.gitkeep', '.DS_Store'])) {
                $languages[$locale] = array_merge(
                    [
                        'locale' => $locale,
                        'name' => $locale,
                        'native_name' => $locale,
                        'direction' => 'ltr',
                        'file_count' => count(File::allFiles($dir)),
                    ],
                    $metadata[$locale] ?? []
                );
            }
        }

        ksort($languages);

        return $languages;
    }

    /**
     * Get translation statistics for all languages.
     */
    protected function getTranslationStats(array $languages): array
    {
        $stats = [];
        $defaultLocale = config('app.locale', 'en');

        foreach ($languages as $locale => $language) {
            $languagePath = $this->languagesPath.'/'.$locale;
            $files = File::allFiles($languagePath);
            $stringCount = 0;

            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $content = include $file->getPathname();
                    if (is_array($content)) {
                        $stringCount += count($this->flattenArray($content));
                    }
                } elseif ($file->getExtension() === 'json') {
                    $content = json_decode(File::get($file->getPathname()), true);
                    if (is_array($content)) {
                        $stringCount += count($this->flattenArray($content));
                    }
                }
            }

            $stats[$locale] = [
                'file_count' => count($files),
                'string_count' => $stringCount,
                'is_default' => $locale === $defaultLocale,
                'completion_percentage' => $this->calculateCompletionPercentage($locale),
            ];
        }

        return $stats;
    }

    /**
     * Calculate translation completion percentage for a language.
     */
    protected function calculateCompletionPercentage(string $locale): int
    {
        $defaultLocale = config('app.locale', 'en');

        if ($locale === $defaultLocale) {
            return 100;
        }

        $defaultPath = $this->languagesPath.'/'.$defaultLocale;
        $languagePath = $this->languagesPath.'/'.$locale;

        if (! File::isDirectory($defaultPath) || ! File::isDirectory($languagePath)) {
            return 0;
        }

        $defaultFiles = File::allFiles($defaultPath);
        $totalStrings = 0;
        $translatedStrings = 0;

        foreach ($defaultFiles as $file) {
            $relativeFileName = $file->getRelativePathname();
            $translationFile = $languagePath.'/'.$relativeFileName;

            if ($file->getExtension() === 'php') {
                $defaultContent = include $file->getPathname();
                $translationContent = File::exists($translationFile) ? include $translationFile : [];

                if (is_array($defaultContent)) {
                    $defaultFlat = $this->flattenArray($defaultContent);
                    $translationFlat = is_array($translationContent) ? $this->flattenArray($translationContent) : [];

                    $totalStrings += count($defaultFlat);
                    foreach ($defaultFlat as $key => $value) {
                        if (isset($translationFlat[$key]) && ! empty($translationFlat[$key])) {
                            $translatedStrings++;
                        }
                    }
                }
            } elseif ($file->getExtension() === 'json') {
                $defaultContent = json_decode(File::get($file->getPathname()), true) ?? [];
                $translationContent = File::exists($translationFile) ? json_decode(File::get($translationFile), true) : [];

                if (is_array($defaultContent)) {
                    $defaultFlat = $this->flattenArray($defaultContent);
                    $translationFlat = is_array($translationContent) ? $this->flattenArray($translationContent) : [];

                    $totalStrings += count($defaultFlat);
                    foreach ($defaultFlat as $key => $value) {
                        if (isset($translationFlat[$key]) && ! empty($translationFlat[$key])) {
                            $translatedStrings++;
                        }
                    }
                }
            }
        }

        return $totalStrings > 0 ? (int) (($translatedStrings / $totalStrings) * 100) : 0;
    }

    /**
     * Get language metadata.
     */
    protected function getLanguageMetadata(string $locale): ?array
    {
        $metadataPath = $this->languagesPath.'/languages.json';

        if (! File::exists($metadataPath)) {
            return null;
        }

        $languages = json_decode(File::get($metadataPath), true) ?? [];

        return $languages[$locale] ?? null;
    }

    /**
     * Save language metadata.
     */
    protected function saveLanguageMetadata(string $locale, array $metadata): void
    {
        $metadataPath = $this->languagesPath.'/languages.json';
        $languages = [];

        if (File::exists($metadataPath)) {
            $languages = json_decode(File::get($metadataPath), true) ?? [];
        }

        $languages[$locale] = $metadata;

        File::put(
            $metadataPath,
            json_encode($languages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Copy translation files from source to destination directory.
     */
    protected function copyTranslationFiles(string $source, string $destination): void
    {
        if (! File::isDirectory($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $files = File::allFiles($source);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $destinationPath = $destination.'/'.$relativePath;

            File::makeDirectory(dirname($destinationPath), 0755, true, true);
            File::copy($file->getPathname(), $destinationPath);
        }
    }

    /**
     * Find language directory in extracted archive.
     */
    protected function findLanguageDirectory(string $path): ?string
    {
        $items = File::directories($path);

        if (empty($items)) {
            // Check if current directory has translation files
            $files = File::allFiles($path);
            foreach ($files as $file) {
                if (in_array($file->getExtension(), $this->supportedFormats)) {
                    return $path;
                }
            }

            return null;
        }

        // Check first level directories for translation files
        foreach ($items as $dir) {
            $files = File::allFiles($dir);
            foreach ($files as $file) {
                if (in_array($file->getExtension(), $this->supportedFormats)) {
                    return $dir;
                }
            }
        }

        // Recursively search in first directory
        return $this->findLanguageDirectory($items[0]);
    }

    /**
     * Get common languages for quick selection.
     */
    protected function getCommonLanguages(): array
    {
        return [
            'es' => 'Español (Spanish)',
            'en' => 'English',
            'fr' => 'Français (French)',
            'de' => 'Deutsch (German)',
            'it' => 'Italiano (Italian)',
            'pt_BR' => 'Português (Brazilian Portuguese)',
            'pt' => 'Português (Portuguese)',
            'ja' => '日本語 (Japanese)',
            'zh_CN' => '简体中文 (Simplified Chinese)',
            'zh_TW' => '繁體中文 (Traditional Chinese)',
            'ru' => 'Русский (Russian)',
            'ar' => 'العربية (Arabic)',
            'ko' => '한국어 (Korean)',
            'nl' => 'Nederlands (Dutch)',
            'pl' => 'Polski (Polish)',
            'vi' => 'Tiếng Việt (Vietnamese)',
            'th' => 'ไทย (Thai)',
            'tr' => 'Türkçe (Turkish)',
            'id' => 'Bahasa Indonesia (Indonesian)',
        ];
    }

    /**
     * Flatten nested array into dot notation.
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix.'.'.$key : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
