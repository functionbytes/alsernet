<?php

namespace Modules\Template\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Core\Models\Setting;
use Modules\Template\Jobs\ClearTemplateCachesJob;
use Modules\Template\Models\Template;
use Modules\Template\Models\TemplateVersion;

class TemplateService
{
    /**
     * Crear un nuevo template
     */
    public function create(array $data): Template
    {
        // Establecer el user_id del creador
        $data['user_id'] = Auth::id();

        // Crear el template (el Observer se encarga de la primera versión automáticamente)
        $template = Template::create($data);

        // Crear directorios en filesystem
        $this->createTemplateDirectories($template);

        return $template->fresh();
    }

    /**
     * Actualizar un template existente
     *
     * Version snapshotting is handled automatically by TemplateObserver::updated()
     * when the content field changes. No manual createVersion() call needed here.
     */
    public function update(Template $template, array $data): Template
    {
        $template->update($data);

        return $template->fresh();
    }

    /**
     * Eliminar un template (soft delete)
     */
    public function delete(Template $template): bool
    {
        return $template->delete();
    }

    /**
     * Activar un template (ponerlo como activo)
     */
    public function activate(Template $template): Template
    {
        DB::transaction(function () use ($template): void {
            Template::where('id', '!=', $template->id)->update(['status' => 'inactive']);
            $template->update(['status' => 'active']);
            Setting::updateOrCreate(
                ['key' => 'template'],
                ['value' => $template->slug]
            );
        });

        Cache::forget('template.active');
        ClearTemplateCachesJob::dispatch();

        return $template->fresh();
    }

    /**
     * Remover/Eliminar un template del filesystem
     */
    public function remove(Template $template): bool
    {
        // No permitir eliminar el template activo
        if ($template->isActive()) {
            throw new \Exception('No se puede eliminar el template activo.');
        }

        // Eliminar directorio del filesystem
        $templatePath = base_path($template->template_path ?? "platform/themes/{$template->slug}");

        if (is_dir($templatePath)) {
            File::deleteDirectory($templatePath);
        }

        // Hacer soft delete en BD
        return $template->delete();
    }

    /**
     * Restaurar a una versión anterior
     */
    public function restoreVersion(TemplateVersion $version): Template
    {
        $template = $version->template;

        // Usar el método del trait Versionable
        $template->restoreVersion($version->id);

        return $template->fresh();
    }

    /**
     * Obtener el template activo
     */
    public function getActive(): ?Template
    {
        return Template::where('status', 'active')->first();
    }

    /**
     * Obtener el nombre del template activo
     */
    public function getActiveName(): string
    {
        return setting('template', 'default');
    }

    /**
     * Verificar si un template es el activo.
     *
     * Usa la columna `status` (fuente canónica que activate() actualiza de forma
     * transaccional) en lugar de setting('template'), que puede desincronizarse.
     */
    public function isActive(Template $template): bool
    {
        return $template->isActive();
    }

    /**
     * Crear estructura de directorios para un template
     */
    private function createTemplateDirectories(Template $template): void
    {
        $basePath = base_path('platform/themes/'.$template->slug);

        // Crear directorios
        foreach (['layouts', 'partials', 'assets', 'assets/css', 'assets/js'] as $dir) {
            $dirPath = $basePath.'/'.$dir;
            if (! is_dir($dirPath)) {
                File::makeDirectory($dirPath, 0755, true, true);
            }
        }

        // Crear template.json
        $jsonData = [
            'name' => $template->name,
            'namespace' => 'Template\\'.str_replace('-', '', ucwords($template->slug, '-')).'\\',
            'description' => $template->description,
            'author' => $template->author ?? 'Alsernet',
            'version' => $template->version ?? '1.0.0',
            'inherit' => $template->inherit,
            'required_plugins' => [],
        ];

        File::put(
            $basePath.'/template.json',
            json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Crear layout Blade por defecto si no existe
        $layoutPath = $basePath.'/layouts/default.blade.php';
        if (! File::exists($layoutPath)) {
            File::put($layoutPath, $this->getDefaultLayoutContent());
        }
    }

    /**
     * Obtener contenido de layout por defecto
     */
    private function getDefaultLayoutContent(): string
    {
        return <<<'BLADE'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @yield('css')
</head>
<body>
    <header>
        @include('template::partials.header')
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        @include('template::partials.footer')
    </footer>

    <script src="{{ asset('js/app.js') }}"></script>
    @yield('js')
</body>
</html>
BLADE;
    }

    /**
     * Prune old versions keeping only recent ones
     */
    public function pruneOldVersions(Template $template, int $keep = 10): int
    {
        return $template->pruneVersions($keep);
    }

    /**
     * Get version history
     */
    public function getVersionHistory(Template $template, int $limit = 50)
    {
        return $template->getVersionHistory($limit);
    }

    /**
     * Compare two versions
     */
    public function compareVersions(Template $template, int $v1Id, int $v2Id): array
    {
        return $template->compareVersions($v1Id, $v2Id);
    }
}
