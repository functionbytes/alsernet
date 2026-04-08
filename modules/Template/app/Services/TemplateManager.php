<?php

namespace Modules\Template\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * TemplateManager - Escanea carpeta platform/themes/ y lee metadatos de templates
 *
 * Implementa el patrón Manager del módulo Theme de Mercosan.
 * Descubre templates en el filesystem leyendo template.json en cada carpeta.
 */
class TemplateManager
{
    protected array $templates = [];

    protected string $templatesPath = 'platform/themes';

    public function __construct()
    {
        $this->loadAllTemplates();
    }

    /**
     * Carga todos los templates disponibles en platform/themes/
     */
    protected function loadAllTemplates(): void
    {
        $this->templates = $this->getAllTemplates();
    }

    /**
     * Obtiene todos los templates escaneando la carpeta platform/themes/
     */
    public function getAllTemplates(): array
    {
        $templates = [];
        $templatesPath = base_path($this->templatesPath);

        if (! is_dir($templatesPath)) {
            return $templates;
        }

        $folders = array_diff(scandir($templatesPath), ['.', '..']);

        foreach ($folders as $folder) {
            $folderPath = $templatesPath.'/'.$folder;

            // Solo procesar directorios
            if (! is_dir($folderPath)) {
                continue;
            }

            $jsonFile = $folderPath.'/template.json';

            // Verificar que existe template.json
            if (! File::exists($jsonFile)) {
                continue;
            }

            $template = $this->loadTemplateJson($jsonFile);

            // Config.php es cargado lazily por el Theme engine al renderizar, no durante el scan.
            // No hacer require aquí - puede contener clases del tema que no están disponibles en boot.

            if (! empty($template)) {
                $templates[$folder] = $template;
            }
        }

        return $templates;
    }

    /**
     * Lee y parsea un archivo template.json
     */
    protected function loadTemplateJson(string $jsonPath): array
    {
        try {
            $content = File::get($jsonPath);
            $data = json_decode($content, true);

            if (! is_array($data)) {
                return [];
            }

            return $data;
        } catch (\Exception $e) {
            Log::warning("Error reading template.json at {$jsonPath}: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Obtiene los templates registrados
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * Obtiene un template específico por clave
     */
    public function getTemplate(string $key): ?array
    {
        return $this->templates[$key] ?? null;
    }

    /**
     * Obtiene el screenshot de un template como data URL base64.
     *
     * Cachea el resultado por 24 horas. El filemtime forma parte del cache key,
     * por lo que cualquier cambio en el archivo invalida el caché automáticamente
     * sin necesidad de flush manual.
     */
    public function getScreenshot(string $templateKey): string
    {
        $screenshotPath = base_path($this->templatesPath.'/'.$templateKey.'/screenshot.png');

        if (! File::exists($screenshotPath)) {
            return $this->defaultScreenshot();
        }

        $cacheKey = 'template:screenshot:'.$templateKey.':'.filemtime($screenshotPath);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($screenshotPath) {
            try {
                return 'data:image/png;base64,'.base64_encode(File::get($screenshotPath));
            } catch (\Exception) {
                return $this->defaultScreenshot();
            }
        });
    }

    /**
     * Retorna un SVG placeholder como data URL para cuando no existe screenshot.
     */
    private function defaultScreenshot(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">'
            .'<rect fill="#f0f0f0" width="400" height="300"/>'
            .'<text x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="16" fill="#999">No screenshot</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Obtiene los presets de un template (si existen en template.json)
     */
    public function getTemplatePresets(string $templateKey): array
    {
        $template = $this->getTemplate($templateKey);

        return $template['presets'] ?? [];
    }

    /**
     * Verifica si un template existe
     */
    public function hasTemplate(string $templateKey): bool
    {
        return isset($this->templates[$templateKey]);
    }

    /**
     * Obtiene el nombre del template activo desde settings
     */
    public function getActiveTemplateName(): string
    {
        return setting('template', 'default');
    }

    /**
     * Obtiene los datos del template activo
     */
    public function getActiveTemplate(): ?array
    {
        $activeKey = $this->getActiveTemplateName();

        return $this->getTemplate($activeKey);
    }

    /**
     * Verifica si un template es el activo
     */
    public function isActive(string $templateKey): bool
    {
        return $this->getActiveTemplateName() === $templateKey;
    }

    /**
     * Obtiene la ruta de la carpeta de un template
     */
    public function getTemplatePath(string $templateKey): string
    {
        return base_path($this->templatesPath.'/'.$templateKey);
    }

    /**
     * Obtiene la ruta completa de un layout dentro de un template
     */
    public function getLayoutPath(string $templateKey, string $layoutName = 'default'): string
    {
        return $this->getTemplatePath($templateKey).'/layouts/'.$layoutName.'.blade.php';
    }

    /**
     * Verifica si existe un layout específico
     */
    public function hasLayout(string $templateKey, string $layoutName = 'default'): bool
    {
        return File::exists($this->getLayoutPath($templateKey, $layoutName));
    }

    /**
     * Escanea el directorio views/templates/ del tema activo y retorna un array [slug => label].
     *
     * Siempre incluye 'default' y 'homepage' como opciones base.
     * Si el directorio no existe, retorna solo las opciones base.
     *
     * @return array<string, string>
     */
    public function getPageTemplates(): array
    {
        $base = [
            'default' => 'Por defecto',
            'homepage' => 'Página de inicio',
        ];

        $activeTheme = $this->getActiveTemplateName();
        $templatesDir = base_path('platform/themes/'.$activeTheme.'/views/templates');

        if (! is_dir($templatesDir)) {
            return $base;
        }

        $files = glob($templatesDir.'/*.blade.php') ?: [];
        $discovered = [];

        foreach ($files as $file) {
            $slug = basename($file, '.blade.php');
            $label = ucwords(str_replace(['-', '_'], ' ', $slug));
            $discovered[$slug] = $label;
        }

        ksort($discovered);

        return array_merge($base, $discovered);
    }

    /**
     * Retorna el namespace de vista de Laravel para un view slug del tema activo.
     *
     * El namespace registrado es 'template' (ver TemplateServiceProvider::registerViews()).
     * La ruta del tema activo se registra como primer path bajo ese namespace.
     *
     * Ejemplos:
     *   'page'              → 'template::views.page'
     *   'templates.default' → 'template::views.templates.default'
     *
     * @param  string  $view  Slug de vista (e.g. 'page', 'templates.default')
     * @param  string|null  $theme  Nombre del tema (ignorado, reservado para compatibilidad futura)
     */
    public function getThemeViewPath(string $view, ?string $theme = null): string
    {
        return 'template::views.'.$view;
    }

    /**
     * Recarga los templates (útil después de cambios en filesystem)
     */
    public function reload(): void
    {
        $this->loadAllTemplates();
    }
}
