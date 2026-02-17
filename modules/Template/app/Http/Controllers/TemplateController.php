<?php

namespace Modules\Template\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Template\Http\Requests\StoreTemplateRequest;
use Modules\Template\Http\Requests\UpdateTemplateRequest;
use Modules\Template\Models\Template;
use Modules\Template\Models\TemplateVersion;
use Modules\Template\Services\TemplateManager;
use Modules\Template\Services\TemplateService;

class TemplateController
{
    public function __construct(
        protected TemplateManager $manager,
        protected TemplateService $service,
    ) {
    }

    /**
     * Mostrar listado de templates en grid 3 columnas (Mercosan pattern)
     */
    public function index(): View
    {
        $templates = $this->manager->getTemplates();
        $activeTemplate = $this->manager->getActiveTemplateName();

        return view('template::settings.index', compact('templates', 'activeTemplate'));
    }

    /**
     * Mostrar formulario crear nuevo template
     */
    public function create(): View
    {
        $templates = Template::where('deleted_at', null)->pluck('name', 'slug')->toArray();

        return view('template::settings.create', compact('templates'));
    }

    /**
     * Guardar nuevo template
     */
    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        try {
            $template = $this->service->create($request->validated());

            return redirect()
                ->route('settings.templates.show', $template)
                ->with('success', __('template::template.template_created'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('template::template.error_creating_template') . ': ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalles de un template
     */
    public function show(Template $template): View
    {
        $versions = $template->versions()->paginate(10);

        return view('template::settings.show', compact('template', 'versions'));
    }

    /**
     * Mostrar formulario editar template
     */
    public function edit(Template $template): View
    {
        $templates = Template::where('deleted_at', null)
            ->where('id', '!=', $template->id)
            ->pluck('name', 'slug')
            ->toArray();

        return view('template::settings.edit', compact('template', 'templates'));
    }

    /**
     * Guardar cambios de un template
     */
    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        try {
            $this->service->update($template, $request->validated());

            return redirect()
                ->route('settings.templates.show', $template)
                ->with('success', __('template::template.template_updated'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('template::template.error_updating_template') . ': ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un template (soft delete)
     */
    public function destroy(Template $template): RedirectResponse
    {
        try {
            $this->service->delete($template);

            return redirect()
                ->route('settings.templates.index')
                ->with('success', __('template::template.template_deleted'));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', __('template::template.error_deleting_template') . ': ' . $e->getMessage());
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

        $zip = new \ZipArchive();
        $tmpPath = $request->file('zip_file')->getPathname();

        if ($zip->open($tmpPath) !== true) {
            return back()->with('error', 'No se pudo abrir el archivo ZIP.');
        }

        $extractPath = sys_get_temp_dir() . '/template_import_' . uniqid();
        $zip->extractTo($extractPath);
        $zip->close();

        // Buscar template.json en raíz o un nivel abajo
        $templateDir = null;
        if (file_exists($extractPath . '/template.json')) {
            $templateDir = $extractPath;
        } else {
            foreach (scandir($extractPath) as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $subPath = $extractPath . '/' . $item;
                if (is_dir($subPath) && file_exists($subPath . '/template.json')) {
                    $templateDir = $subPath;
                    break;
                }
            }
        }

        if (!$templateDir) {
            \Illuminate\Support\Facades\File::deleteDirectory($extractPath);
            return back()->with('error', 'El ZIP no contiene un archivo template.json válido.');
        }

        $meta = json_decode(file_get_contents($templateDir . '/template.json'), true);
        $slug = $meta['slug'] ?? basename($templateDir);
        $slug = preg_replace('/[^a-z0-9\-_]/', '-', strtolower($slug));

        $destination = base_path('platform/themes/' . $slug);

        if (is_dir($destination)) {
            \Illuminate\Support\Facades\File::deleteDirectory($extractPath);
            return back()->with('error', "Ya existe una plantilla con el slug \"{$slug}\".");
        }

        \Illuminate\Support\Facades\File::moveDirectory($templateDir, $destination);
        \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

        return redirect()
            ->route('settings.templates.index')
            ->with('success', 'Plantilla "' . ($meta['name'] ?? $slug) . '" importada correctamente.');
    }

    /**
     * AJAX: Activar un template (Mercosan pattern)
     */
    public function postActivateTemplate(Request $request): JsonResponse
    {
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
        $versions = $template->versions()->paginate(15);

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
}
