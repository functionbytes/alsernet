<?php

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Modules\Template\Http\Requests\StoreTemplateRequest;
use Modules\Template\Http\Requests\UpdateTemplateRequest;
use Modules\Template\Models\Shortcode;
use Modules\Template\Models\Template;
use Modules\Template\Services\TemplateManager;
use Modules\Template\Services\TemplateService;

class TemplateController extends Controller
{
    public function __construct(
        protected TemplateManager $manager,
        protected TemplateService $service,
    ) {}

    /**
     * Mostrar listado de templates en grid 3 columnas (Mercosan pattern)
     */
    public function index(): View
    {
        $this->authorize('viewAny', Template::class);

        $templates = $this->manager->getTemplates();
        $activeTemplate = $this->manager->getActiveTemplateName();

        return view('template::settings.index', compact('templates', 'activeTemplate'));
    }

    /**
     * Mostrar formulario crear nuevo template
     */
    public function create(): View
    {
        $this->authorize('create', Template::class);

        $templates = Template::query()->pluck('name', 'slug')->toArray();
        $shortcodes = Shortcode::active()->get(['id', 'key', 'name', 'description', 'icon', 'shortcode_template']);

        return view('template::settings.create', compact('templates', 'shortcodes'));
    }

    /**
     * Guardar nuevo template
     */
    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', Template::class);
        try {
            $template = $this->service->create($request->validated());

            return redirect()
                ->route('settings.templates.show', $template)
                ->with('success', __('template::template.template_created'));
        } catch (\Exception $e) {
            Log::error('Template creation failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', __('template::template.error_creating_template'));
        }
    }

    /**
     * Mostrar detalles de un template
     */
    public function show(Template $template): View
    {
        $this->authorize('view', $template);

        $versions = $template->versions()->paginate(paginationNumber());

        return view('template::settings.show', compact('template', 'versions'));
    }

    /**
     * Mostrar formulario editar template
     */
    public function edit(Template $template): View
    {
        $this->authorize('update', $template);

        $templates = Template::query()
            ->where('id', '!=', $template->id)
            ->pluck('name', 'slug')
            ->toArray();
        $shortcodes = Shortcode::active()->get(['id', 'key', 'name', 'description', 'icon', 'shortcode_template']);

        return view('template::settings.edit', compact('template', 'templates', 'shortcodes'));
    }

    /**
     * Guardar cambios de un template
     */
    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        $this->authorize('update', $template);

        try {
            $this->service->update($template, $request->validated());

            return redirect()
                ->route('settings.templates.show', $template)
                ->with('success', __('template::template.template_updated'));
        } catch (\Exception $e) {
            Log::error('Template update failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', __('template::template.error_updating_template'));
        }
    }

    /**
     * AJAX: Generar vista previa del contenido del template
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:100000'],
        ]);

        try {
            $content = $this->sanitizeTemplateContent($request->input('content'));

            $html = Blade::render($content, [
                'title' => 'Título de ejemplo',
                'description' => 'Descripción de ejemplo para la vista previa.',
                'content' => '<p>Este es el contenido de ejemplo que se mostrará en el template.</p><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
                'keywords' => 'ejemplo, preview, template',
                'canonical' => url('/'),
            ]);

            return response()->json(['html' => $html]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Template preview failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Error al procesar la plantilla.'], 422);
        }
    }

    /**
     * Eliminar un template (soft delete)
     */
    public function destroy(Template $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        try {
            $this->service->delete($template);

            return redirect()
                ->route('settings.templates.index')
                ->with('success', __('template::template.template_deleted'));
        } catch (\Exception $e) {
            Log::error('Template deletion failed', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->with('error', __('template::template.error_deleting_template'));
        }
    }

    /**
     * Importar una plantilla desde un archivo ZIP
     */
    public function importZip(Request $request): RedirectResponse
    {
        $request->validate([
            'zip_file' => ['required', 'file', 'mimes:zip', 'max:20480'],
        ]);

        $zip = new \ZipArchive;
        $tmpPath = $request->file('zip_file')->getPathname();

        if ($zip->open($tmpPath) !== true) {
            return back()->with('error', 'No se pudo abrir el archivo ZIP.');
        }

        $extractPath = sys_get_temp_dir().'/template_import_'.uniqid();
        mkdir($extractPath, 0755, true);
        $realExtractPath = realpath($extractPath);

        // Validate all entry paths before extracting to prevent path traversal
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            if ($entry === false || str_contains($entry, '..')) {
                $zip->close();
                \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

                return back()->with('error', 'ZIP inválido: se detectó una ruta maliciosa.');
            }
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Secondary check: verify no extracted file escaped the destination directory
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractPath));
        foreach ($iterator as $file) {
            $resolved = realpath($file->getPathname());
            if ($resolved === false || ! str_starts_with($resolved, $realExtractPath)) {
                \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

                return back()->with('error', 'ZIP inválido: se detectó una ruta maliciosa tras la extracción.');
            }
        }

        // Buscar template.json en raíz o un nivel abajo
        $templateDir = null;
        if (file_exists($extractPath.'/template.json')) {
            $templateDir = $extractPath;
        } else {
            foreach (scandir($extractPath) as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $subPath = $extractPath.'/'.$item;
                if (is_dir($subPath) && file_exists($subPath.'/template.json')) {
                    $templateDir = $subPath;
                    break;
                }
            }
        }

        if (! $templateDir) {
            \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

            return back()->with('error', 'El ZIP no contiene un archivo template.json válido.');
        }

        $meta = json_decode(file_get_contents($templateDir.'/template.json'), true);
        $slug = $meta['slug'] ?? basename($templateDir);
        $slug = preg_replace('/[^a-z0-9\-_]/', '-', strtolower($slug));

        $destination = base_path('platform/themes/'.$slug);

        if (is_dir($destination)) {
            \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

            return back()->with('error', "Ya existe una plantilla con el slug \"{$slug}\".");
        }

        \Illuminate\Support\Facades\File::moveDirectory($templateDir, $destination);
        \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

        return redirect()
            ->route('settings.templates.index')
            ->with('success', 'Plantilla "'.($meta['name'] ?? $slug).'" importada correctamente.');
    }

    /**
     * AJAX: Activar un template (Mercosan pattern)
     */
    public function postActivateTemplate(Request $request): JsonResponse
    {
        $this->authorize('update', Template::class);

        try {
            $request->validate([
                'template' => 'required|string|exists:templates,slug',
            ]);

            $template = Template::where('slug', $request->input('template'))->firstOrFail();

            $this->service->activate($template);

            return response()->json([
                'error' => false,
                'message' => __('template::template.template_activated'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => __('template::template.error_activating_template'),
            ], 422);
        }
    }

    /**
     * AJAX: Remover/Eliminar un template (Mercosan pattern)
     */
    public function postRemoveTemplate(Request $request): JsonResponse
    {
        $this->authorize('delete', Template::class);

        try {
            $request->validate([
                'template' => 'required|string|exists:templates,slug',
            ]);

            $template = Template::where('slug', $request->input('template'))->firstOrFail();

            // No permitir eliminar el template activo
            if ($this->service->isActive($template)) {
                return response()->json([
                    'error' => true,
                    'message' => __('template::template.cannot_remove_active'),
                ], 422);
            }

            $this->service->remove($template);

            return response()->json([
                'error' => false,
                'message' => __('template::template.template_removed'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => __('template::template.error_removing_template'),
            ], 422);
        }
    }

    /**
     * Listar versiones de un template
     */
    public function versionsIndex(Template $template): View
    {
        $versions = $template->versions()->paginate(paginationNumber());

        return view('template::settings.versions.index', compact('template', 'versions'));
    }

    /**
     * Ver una versión específica
     */
    public function showVersion(Template $template, int $version): View
    {
        $templateVersion = $template->versions()
            ->where('version', $version)
            ->firstOrFail();

        return view('template::settings.versions.show', compact('template', 'templateVersion'));
    }

    /**
     * Restaurar a una versión anterior
     */
    public function restoreVersion(Template $template, int $version): RedirectResponse
    {
        try {
            $templateVersion = $template->versions()
                ->where('version', $version)
                ->firstOrFail();

            $this->service->restoreVersion($templateVersion);

            return redirect()
                ->route('settings.templates.show', $template)
                ->with('success', __('template::template.template_restored'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('template::template.error_restoring_version'));
        }
    }

    /**
     * Comparar dos versiones
     */
    public function compareVersions(Template $template, int $version, int $compareWith): View
    {
        $v1 = $template->versions()
            ->where('version', $version)
            ->firstOrFail();

        $v2 = $template->versions()
            ->where('version', $compareWith)
            ->firstOrFail();

        $comparison = $template->compareVersions($v1->id, $v2->id);

        return view('template::settings.versions.compare', compact('template', 'v1', 'v2', 'comparison'));
    }

    /**
     * Sanitize template content to reject dangerous Blade/PHP directives before rendering.
     *
     * @throws \InvalidArgumentException
     */
    private function sanitizeTemplateContent(string $content): string
    {
        $dangerousPatterns = [
            // PHP execution
            '/@php/i',
            '/<\?php/i',
            '/<\?=/i',
            '/`[^`]*`/i',
            '/@eval/i',

            // Raw output (already bypasses Blade escaping)
            '/\{\{!!/i',

            // Blade template inclusion / inheritance
            '/@include(If|When|First)?/i',
            '/@extends/i',
            '/@section/i',
            '/@component/i',
            '/@slot/i',

            // Service/container injection
            '/@inject/i',

            // Sensitive function calls (inside {{ }} or anywhere in template)
            '/\bconfig\s*\(/i',
            '/\benv\s*\(/i',
            '/\bapp\s*\(/i',
            '/\bresolve\s*\(/i',

            // Filesystem & command execution
            '/\bfile_get_contents\s*\(/i',
            '/\bfile_put_contents\s*\(/i',
            '/\bexec\s*\(/i',
            '/\bshell_exec\s*\(/i',
            '/\bsystem\s*\(/i',
            '/\bpassthru\s*\(/i',
            '/\bproc_open\s*\(/i',
            '/\bpopen\s*\(/i',

            // Path helpers that expose server layout
            '/\bbase_path\s*\(/i',
            '/\bstorage_path\s*\(/i',
            '/\bapp_path\s*\(/i',
            '/\bpublic_path\s*\(/i',
            '/\bresource_path\s*\(/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new \InvalidArgumentException('El contenido contiene directivas no permitidas.');
            }
        }

        return $content;
    }
}
