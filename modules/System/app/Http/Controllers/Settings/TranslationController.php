<?php

namespace Modules\System\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TranslationController extends Controller
{
    protected array $availableLocales = ['es', 'en', 'de', 'it', 'pt'];

    protected array $translationFileLabels = [
        'documents' => 'Documentos',
        'auth' => 'Autenticación',
        'messages' => 'Mensajes',
        'validation' => 'Validación',
        'pagination' => 'Paginación',
        'passwords' => 'Contraseñas',
        'helpdesk' => 'Helpdesk',
        'builder' => 'Constructor',
    ];

    /**
     * Mostrar lista de archivos de traducción
     */
    public function index(Request $request)
    {
        // Detectar dinámicamente todos los archivos de traducción
        $baseLocale = 'es';
        $basePath = resource_path("lang/{$baseLocale}");
        $availableFiles = [];

        if (File::isDirectory($basePath)) {
            $files = File::files($basePath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $filename = $file->getFilenameWithoutExtension();
                    $availableFiles[$filename] = $this->translationFileLabels[$filename] ?? ucfirst(str_replace('_', ' ', $filename));
                }
            }
        }

        // Agrupar por archivo
        $translationsByFile = [];

        foreach ($availableFiles as $file => $label) {
            $fileData = [
                'file' => $file,
                'label' => $label,
                'locales' => [],
            ];

            foreach ($this->availableLocales as $locale) {
                $path = resource_path("lang/{$locale}/{$file}.php");

                if (File::exists($path)) {
                    $fileContent = File::getRequire($path);

                    $fileData['locales'][] = [
                        'locale' => $locale,
                        'locale_label' => $this->getLocaleLabel($locale),
                        'path' => $path,
                        'content' => $fileContent,
                        'count' => $this->countTranslationKeys($fileContent),
                    ];
                }
            }

            if (! empty($fileData['locales'])) {
                $translationsByFile[$file] = $fileData;
            }
        }

        $searchQuery = $request->input('search', '');

        return view('theme.views.backups.translations.index', [
            'translationsByFile' => $translationsByFile,
            'locales' => $this->availableLocales,
            'availableFiles' => $availableFiles,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Mostrar formulario de edición de un archivo de traducción
     */
    public function edit(string $locale, string $file)
    {
        if (! in_array($locale, $this->availableLocales)) {
            abort(404, 'Locale not found');
        }

        $path = resource_path("lang/{$locale}/{$file}.php");

        if (! File::exists($path)) {
            abort(404, 'Translation file not found');
        }

        $content = File::getRequire($path);
        $fileLabel = $this->translationFileLabels[$file] ?? ucfirst(str_replace('_', ' ', $file));

        // Detectar todas las claves disponibles desde el locale base
        $baseContent = File::getRequire(resource_path("lang/es/{$file}.php"));

        return view('theme.views.backups.translations.edit', [
            'locale' => $locale,
            'locale_label' => $this->getLocaleLabel($locale),
            'file' => $file,
            'file_label' => $fileLabel,
            'content' => $content,
            'baseContent' => $baseContent,
            'path' => $path,
        ]);
    }

    /**
     * Guardar cambios en un archivo de traducción
     */
    public function update(Request $request, string $locale, string $file)
    {
        if (! in_array($locale, $this->availableLocales)) {
            abort(404, 'Locale not found');
        }

        $path = resource_path("lang/{$locale}/{$file}.php");

        if (! File::exists($path)) {
            abort(404, 'Translation file not found');
        }

        try {
            // Obtener el contenido actual
            $content = File::getRequire($path);

            // Procesar los datos enviados
            $translations = $request->input('translations', []);

            // Actualizar recursivamente el array de traducciones
            $content = $this->updateTranslationArray($content, $translations);

            // Guardar el archivo
            $fileContent = "<?php\n\nreturn ".var_export($content, true).";\n";

            File::put($path, $fileContent);

            $fileLabel = $this->translationFileLabels[$file] ?? ucfirst(str_replace('_', ' ', $file));

            return redirect()
                ->route('manager.backups.translations.edit', [$locale, $file])
                ->with('success', "Traducción de $fileLabel actualizada correctamente");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al guardar las traducciones: '.$e->getMessage());
        }
    }

    /**
     * Actualizar recursivamente un array de traducciones
     */
    protected function updateTranslationArray(array $original, array $updates): array
    {
        foreach ($updates as $key => $value) {
            if (is_array($value) && isset($original[$key]) && is_array($original[$key])) {
                $original[$key] = $this->updateTranslationArray($original[$key], $value);
            } else {
                $original[$key] = $value;
            }
        }

        return $original;
    }

    /**
     * Contar las claves de traducción
     */
    protected function countTranslationKeys(array $array, int $count = 0): int
    {
        foreach ($array as $value) {
            if (is_array($value)) {
                $count = $this->countTranslationKeys($value, $count);
            } else {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Obtener el label legible de un locale
     */
    protected function getLocaleLabel(string $locale): string
    {
        return match ($locale) {
            'es' => 'Español',
            'en' => 'English',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            default => Str::upper($locale),
        };
    }
}
