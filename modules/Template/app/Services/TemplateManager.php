<?php

namespace Modules\Template\Services;

use Illuminate\Support\Facades\File;

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

        if (!is_dir($templatesPath)) {
            return $templates;
        }

        $folders = array_diff(scandir($templatesPath), ['.', '..']);

        foreach ($folders as $folder) {
            $folderPath = $templatesPath . '/' . $folder;

            // Solo procesar directorios
            if (!is_dir($folderPath)) {
                continue;
            }

            $jsonFile = $folderPath . '/template.json';

            // Verificar que existe template.json
            if (!File::exists($jsonFile)) {
                continue;
            }

            $template = $this->loadTemplateJson($jsonFile);

            // Cargar y mergear config.php si existe
            $configPath = $folderPath . '/config.php';
            if (File::exists($configPath)) {
                $config = require $configPath;
                if (is_array($config)) {
                    $template = array_merge($template, $config);
                }
            }

            if (!empty($template)) {
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

            if (!is_array($data)) {
                return [];
            }

            return $data;
        } catch (\Exception $e) {
            \Log::warning("Error reading template.json at {$jsonPath}: {$e->getMessage()}");

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
     * Obtiene la ruta del screenshot de un template
     *
     * Si existe screenshot.png, retorna como data:// URL base64
     */
    public function getScreenshot(string $templateKey): string
    {
        $screenshotPath = base_path($this->templatesPath . '/' . $templateKey . '/screenshot.png');

        if (!File::exists($screenshotPath)) {
            // Retornar un placeholder/default image si no existe
            return 'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect fill="#f0f0f0" width="400" height="300"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="16" fill="#999">No screenshot</text></svg>'
            );
        }

        try {
            $imageData = File::get($screenshotPath);
            $base64 = base64_encode($imageData);

            return 'data:image/png;base64,' . $base64;
        } catch (\Exception $e) {
            \Log::warning("Error reading screenshot for template {$templateKey}: {$e->getMessage()}");

            // Retornar placeholder en caso de error
            return 'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect fill="#f0f0f0" width="400" height="300"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="16" fill="#999">Error loading</text></svg>'
            );
        }
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
        return base_path($this->templatesPath . '/' . $templateKey);
    }

    /**
     * Obtiene la ruta completa de un layout dentro de un template
     */
    public function getLayoutPath(string $templateKey, string $layoutName = 'default'): string
    {
        return $this->getTemplatePath($templateKey) . '/layouts/' . $layoutName . '.blade.php';
    }

    /**
     * Verifica si existe un layout específico
     */
    public function hasLayout(string $templateKey, string $layoutName = 'default'): bool
    {
        return File::exists($this->getLayoutPath($templateKey, $layoutName));
    }

    /**
     * Recarga los templates (útil después de cambios en filesystem)
     */
    public function reload(): void
    {
        $this->loadAllTemplates();
    }
}
