<?php

namespace Modules\Mailer\Http\Controllers;

use App\Http\Controllers\Controller;
use DOMDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Mailer\Http\Requests\StoreMailerTemplateRequest;
use Modules\Mailer\Http\Requests\UpdateMailerTemplateRequest;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;
use Modules\Mailer\Models\MailerTemplateVersion;
use Modules\Mailer\Services\MailerTemplateRendererService;
use Modules\Mailer\Services\MailerVariableReplacementService;
use Modules\Mailer\Services\MailerVariableService;

class MailerTemplateController extends Controller
{
    /**
     * Listar todos los templates de email (únicos por key, no por idioma)
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MailerTemplate::class);
        $search = $request->input('search');
        $module = $request->input('module');
        $langId = $request->input('lang_id', 1); // Default to first language for preview

        $query = MailerTemplate::distinct('key')
            ->select('mailer_templates.*')
            ->orderByDesc('updated_at');

        // Búsqueda por nombre, key o descripción
        if ($search) {
            $query->search($search);
        }

        // Filtrar por módulo
        if ($module) {
            $query->module($module);
        }

        // Eager load translations for selected language
        $query->with(['translations' => function ($q) use ($langId) {
            $q->where('lang_id', $langId);
        }]);

        $templates = $query->paginate(paginationNumber());

        // Obtener módulos únicos para filtro
        $modules = MailerTemplate::distinct('module')->pluck('module')->toArray();

        // Obtener idiomas disponibles
        $langs = MailerLang::available()->get();

        return view('mailer::templates.index', [
            'templates' => $templates,
            'search' => $search,
            'module' => $module,
            'langId' => $langId,
            'modules' => $modules,
            'langs' => $langs,
        ]);
    }

    /**
     * Mostrar formulario para crear nuevo template
     */
    public function create(Request $request): View
    {
        $this->authorize('create', MailerTemplate::class);
        $template = new MailerTemplate;
        $layouts = MailerLayout::where('type', 'layout')
            ->enabled()
            ->with('translations')
            ->orderBy('alias')
            ->get();

        // Obtener módulo del request o usar 'documents' como default
        $module = $request->input('module', 'documents');

        // Obtener idioma del request (default: primer idioma disponible)
        $langId = $request->input('lang_id');
        if (! $langId) {
            $defaultLang = MailerLang::available()->first();
            $langId = $defaultLang?->id;
        }

        // Estructura base para el nuevo template
        $baseContent = MailerTemplate::getStructureForModule($module);

        $variables = MailerTemplate::defaultVariables($module);

        // Obtener idiomas disponibles
        $langs = MailerLang::available()->get();

        return view('mailer::templates.create', [
            'template' => $template,
            'layouts' => $layouts,
            'module' => $module,
            'langId' => $langId,
            'currentLangId' => $langId,
            'variables' => $variables,
            'baseContent' => $baseContent,
            'langs' => $langs,
        ]);
    }

    /**
     * Guardar nuevo template
     */
    public function store(StoreMailerTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', MailerTemplate::class);

        $validated = $request->validated();

        try {
            // Validar que no exista ya un template con esta key
            $existing = MailerTemplate::where('key', $validated['key'])->first();

            if ($existing) {
                return redirect()
                    ->back()
                    ->with('error', 'Ya existe un template con esta clave (key)')
                    ->withInput();
            }

            // Obtener todos los idiomas disponibles
            $allLangs = MailerLang::available()->get();

            if ($allLangs->isEmpty()) {
                return redirect()
                    ->back()
                    ->with('error', 'No hay idiomas disponibles en el sistema')
                    ->withInput();
            }

            // Crear UN template principal (sin subject/content, solo metadata)
            $template = MailerTemplate::create([
                'key' => $validated['key'],
                'name' => $validated['name'],
                'layout_id' => $validated['layout_id'] ?? null,
                'module' => $validated['module'],
                'description' => $validated['description'] ?? null,
                'is_enabled' => true,
                'is_protected' => $validated['is_protected'] ?? false,
            ]);

            // Crear traducciones para todos los idiomas
            foreach ($allLangs as $lang) {
                MailerTemplateLang::create([
                    'mailer_template_id' => $template->id,
                    'lang_id' => $lang->id,
                    'subject' => $validated['subject'],
                    'preheader' => $validated['preheader'] ?? null,
                    'content' => $validated['content'],
                ]);
            }

            activity()
                ->performedOn($template)
                ->causedBy(auth()->user())
                ->withProperties(['key' => $template->key, 'module' => $template->module])
                ->log('Mailer template created');

            return redirect()
                ->route('mailers.templates.edit', [
                    'uid' => $template->uid,
                    'lang_id' => $validated['lang_id'],
                ])
                ->with('success', "Template '{$validated['name']}' creado exitosamente para todos los idiomas (".count($allLangs).' versiones)');
        } catch (\Exception $e) {
            Log::error('Error creating email template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated ?? [],
            ]);

            return redirect()
                ->back()
                ->with('error', 'Error al crear el template: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar formulario para editar template
     */
    public function edit(Request $request, $uid, $translation_uid = null): View
    {
        $template = MailerTemplate::where('uid', $uid)->firstOrFail();
        $this->authorize('update', $template);

        // Si viene translation_uid, cargar por UID de traducción
        if ($translation_uid) {
            $translation = MailerTemplateLang::where('uid', $translation_uid)
                ->where('mailer_template_id', $template->id)
                ->firstOrFail();
            $langId = $translation->lang_id;
        } else {
            // Obtener idioma actual (del request o default a 1)
            $langId = $request->input('lang_id', 1);
            // Obtener la traducción para el idioma actual
            $translation = $template->translate($langId);
        }

        // Si no existe traducción para este idioma, crear una vacía
        if (! $translation) {
            $translation = new MailerTemplateLang([
                'mailer_template_id' => $template->id,
                'lang_id' => $langId,
                'subject' => '',
                'preheader' => '',
                'content' => '',
            ]);
        }

        $layouts = MailerLayout::where('type', 'layout')
            ->enabled()
            ->with('translations')
            ->orderBy('alias')
            ->get();
        $variables = $template->getAvailableVariables();

        // Obtener idiomas disponibles para mostrar selector
        $langs = MailerLang::available()->get();

        // Obtener otras traducciones para este template
        $otherTranslations = $template->translations()
            ->where('lang_id', '!=', $langId)
            ->with('lang')
            ->get();

        return view('mailer::templates.edit', [
            'template' => $template,
            'translation' => $translation,
            'currentLangId' => $langId,
            'layouts' => $layouts,
            'variables' => $variables,
            'langs' => $langs,
            'otherTranslations' => $otherTranslations,
            'otherLangs' => $otherTranslations, // Keep for backwards compatibility
        ]);
    }

    /**
     * Actualizar template
     */
    public function update(UpdateMailerTemplateRequest $request, $uid): RedirectResponse
    {
        $template = MailerTemplate::where('uid', $uid)->firstOrFail();
        $this->authorize('update', $template);

        $validated = $request->validated();

        try {
            // Actualizar metadata del template (name, layout_id, is_enabled, etc.)
            $template->update([
                'layout_id' => $validated['layout_id'] ?? null,
                'is_enabled' => $validated['is_enabled'] ?? true,
                'is_protected' => $validated['is_protected'] ?? false,
                'description' => $validated['description'] ?? null,
            ]);

            // Obtener la traducción para el idioma actual
            $translation = $template->translate($validated['lang_id']);

            if ($translation) {
                // Guardar versión antes de sobrescribir
                MailerTemplateVersion::create([
                    'mailer_template_id' => $template->id,
                    'lang_id' => $translation->lang_id,
                    'created_by' => auth()->id(),
                    'subject' => $translation->subject,
                    'content' => $translation->content,
                    'change_note' => $request->input('change_note'),
                ]);

                $translation->update([
                    'subject' => $validated['subject'],
                    'preheader' => $validated['preheader'] ?? null,
                    'content' => $validated['content'],
                ]);
            } else {
                // Crear nueva traducción si no existe
                $translation = MailerTemplateLang::create([
                    'mailer_template_id' => $template->id,
                    'lang_id' => $validated['lang_id'],
                    'subject' => $validated['subject'],
                    'preheader' => $validated['preheader'] ?? null,
                    'content' => $validated['content'],
                ]);
            }

            activity()
                ->performedOn($template)
                ->causedBy(auth()->user())
                ->withProperties(['key' => $template->key, 'lang_id' => $validated['lang_id']])
                ->log('Mailer template updated');

            return redirect()
                ->route('mailers.templates.edit', [
                    'uid' => $template->uid,
                    'translation_uid' => $translation->uid,
                ])
                ->with('success', 'Template actualizado exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al actualizar: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Obtener preview HTML del template (vista completa)
     */
    public function preview(Request $request, $uid): View|RedirectResponse
    {
        $template = MailerTemplate::where('uid', $uid)->firstOrFail();

        // Obtener idioma actual (del request o default a 1)
        $langId = $request->input('lang_id', 1);

        // Obtener traducción para el idioma especificado
        $translation = $template->translate($langId);

        if (! $translation) {
            return redirect()->back()->with('error', 'No existe traducción para este idioma');
        }

        // Usar el MISMO servicio que se usa para enviar emails reales
        // Esto garantiza que el preview sea idéntico al email enviado
        $variables = $this->getPreviewVariables($template, $langId);
        $html = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

        return view('mailer::templates.preview', [
            'template' => $template,
            'translation' => $translation,
            'html' => $html,
        ]);
    }

    /**
     * Obtener preview en AJAX (para split-panel en vivo)
     */
    public function previewAjax(Request $request, $uid): JsonResponse
    {
        try {
            $template = MailerTemplate::where('uid', $uid)->firstOrFail();

            // Obtener idioma actual (del request o default a 1)
            $langId = $request->input('lang_id', 1);

            // Obtener layout_id del request (para live preview sin guardar)
            $overrideLayoutId = $request->input('layout_id');

            // Obtener contenido del editor (live preview)
            $customContent = $request->input('content');

            // Si hay override de layout_id, actualizarlo temporalmente en el template
            $originalLayoutId = $template->layout_id;
            if ($overrideLayoutId !== null) {
                $template->layout_id = $overrideLayoutId;
            }

            // Si hay contenido custom, actualizar la traducción temporalmente
            if ($customContent !== null) {
                $translation = $template->translate($langId);
                if ($translation) {
                    $originalContent = $translation->content;
                    $translation->content = $customContent;
                }
            }

            // Usar el MISMO servicio que se usa para enviar emails reales
            // Esto garantiza que el preview sea idéntico al email enviado
            $variables = $this->getPreviewVariables($template, $langId);
            $html = MailerTemplateRendererService::renderEmailTemplate($template, $variables, $langId);

            // Restaurar valores originales
            $template->layout_id = $originalLayoutId;
            if (isset($originalContent) && isset($translation)) {
                $translation->content = $originalContent;
            }

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en previewAjax: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'html' => '<div class="alert alert-danger p-3">Error: '.e($e->getMessage()).'</div>',
            ], 500);
        }
    }

    /**
     * Obtener variables de ejemplo para el preview
     */
    private function getPreviewVariables(MailerTemplate $template, ?int $langId = null): array
    {
        $langId = $langId ?? 1;

        return MailerVariableReplacementService::getPreviewVariablesForTemplate($template, $langId);
    }

    /**
     * Historial de versiones de un template para un idioma
     */
    public function versions(Request $request, string $uid): View
    {
        $template = MailerTemplate::where('uid', $uid)->firstOrFail();
        $this->authorize('update', $template);

        $langId = $request->integer('lang_id', 1);

        $versions = MailerTemplateVersion::where('mailer_template_id', $template->id)
            ->where('lang_id', $langId)
            ->with('author:id,name,email')
            ->latest()
            ->paginate(paginationNumber());

        $langs = MailerLang::available()->get();

        return view('mailer::templates.versions', compact('template', 'versions', 'langs', 'langId'));
    }

    /**
     * Restaurar una versión anterior como contenido activo
     */
    public function restoreVersion(Request $request, string $uid, MailerTemplateVersion $version): RedirectResponse
    {
        $template = MailerTemplate::where('uid', $uid)->firstOrFail();
        $this->authorize('update', $template);

        $translation = $template->translate($version->lang_id);

        if (! $translation) {
            return redirect()->back()->with('error', 'No existe traducción para restaurar.');
        }

        // Guardar estado actual como nueva versión antes de restaurar
        MailerTemplateVersion::create([
            'mailer_template_id' => $template->id,
            'lang_id' => $translation->lang_id,
            'created_by' => auth()->id(),
            'subject' => $translation->subject,
            'content' => $translation->content,
            'change_note' => 'Auto-guardado antes de restaurar versión #'.$version->id,
        ]);

        $translation->update([
            'subject' => $version->subject,
            'content' => $version->content,
        ]);

        return redirect()
            ->route('mailers.templates.edit', ['uid' => $template->uid])
            ->with('success', 'Versión #'.$version->id.' restaurada correctamente.');
    }

    /**
     * Eliminar template
     */
    public function destroy($uid): RedirectResponse
    {
        $template = MailerTemplate::where('uid', $uid)->firstOrFail();
        $this->authorize('delete', $template);

        // Verificar si el template está protegido
        if ($template->is_protected) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar un template protegido. Desactiva la protección primero si deseas eliminarlo.');
        }

        try {
            $name = $template->name;
            $key = $template->key;

            activity()
                ->performedOn($template)
                ->causedBy(auth()->user())
                ->withProperties(['name' => $name, 'key' => $key])
                ->log('Mailer template deleted');

            // Delete all translations first (if not using cascade)
            $template->translations()->delete();

            // Delete the template
            $template->delete();

            return redirect()
                ->route('mailers.templates.index')
                ->with('success', "Template '{$name}' eliminado exitosamente");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar: '.$e->getMessage());
        }
    }

    /**
     * Formatear HTML (API endpoint)
     */
    public function formatHtml(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'html' => 'required|string',
        ]);

        try {
            $formatted = $this->beautifyHtml($validated['html']);

            return response()->json([
                'success' => true,
                'formatted' => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al formatear HTML',
            ], 500);
        }
    }

    /**
     * Formatear HTML con indentación
     */
    private function beautifyHtml(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        // Suprimir errores de HTML malformado
        libxml_use_internal_errors(true);

        // Cargar HTML
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Limpiar errores
        libxml_clear_errors();

        // Obtener HTML formateado
        $formatted = $dom->saveHTML();

        // Remover la declaración XML que se agregó
        $formatted = str_replace('<?xml encoding="UTF-8">', '', $formatted);

        return trim($formatted);
    }

    /**
     * Obtener variables disponibles en AJAX (alias para variables())
     */
    public function getVariables($uid): JsonResponse
    {
        return $this->variables($uid);
    }

    /**
     * Obtener variables disponibles para el template (usado por edit.blade.php)
     */
    public function variables($uid): JsonResponse
    {
        try {
            $template = MailerTemplate::where('uid', $uid)->firstOrFail();

            return response()->json([
                'success' => true,
                'variables' => MailerVariableService::getGroupedForModule($template->module),
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading variables: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'variables' => [],
            ], 500);
        }
    }

    /**
     * Get variables for a specific module (for create form)
     * Used when creating a new template to load variables based on selected module
     */
    public function variablesByModule(Request $request): JsonResponse
    {
        $module = $request->query('module');

        if (! $module) {
            return response()->json([
                'success' => false,
                'message' => 'Module parameter is required',
                'variables' => [],
            ], 400);
        }

        try {
            return response()->json([
                'success' => true,
                'variables' => MailerVariableService::getGroupedForModule($module),
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading variables by module: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'variables' => [],
            ], 500);
        }
    }

    /**
     * Cambiar estado (enabled/disabled)
     */
    public function toggleStatus($uid): RedirectResponse
    {
        $template = MailerTemplate::where('uid', $uid)->firstOrFail();
        $this->authorize('update', $template);

        $template->is_enabled = ! $template->is_enabled;
        $template->save();

        $status = $template->is_enabled ? 'Habilitado' : 'Deshabilitado';

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->withProperties(['key' => $template->key, 'is_enabled' => $template->is_enabled])
            ->log('Mailer template status toggled');

        return redirect()
            ->back()
            ->with('success', "Template '{$template->name}' $status");
    }

    /**
     * Enviar email de prueba
     */
    public function sendTest(Request $request, $uid): RedirectResponse
    {
        $template = MailerTemplate::where('uid', $uid)->firstOrFail();

        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            // Obtener traducción (usar idioma 1 como default para emails de prueba)
            $translation = $template->translate(1);

            if (! $translation) {
                return redirect()->back()->with('error', 'No existe traducción para enviar email de prueba');
            }

            // Render using the canonical renderer service (same as live preview)
            $variables = $this->getPreviewVariables($template, 1);
            $html = MailerTemplateRendererService::renderEmailTemplate($template, $variables, 1);

            // Enviar email usando el facade Mail correctamente
            Mail::send([], [], function ($message) use ($translation, $validated, $html) {
                $message->to($validated['test_email'])
                    ->subject($translation->subject)
                    ->html($html);
            });

            return redirect()
                ->back()
                ->with('success', 'Email de prueba enviado a '.$validated['test_email']);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al enviar email: '.$e->getMessage());
        }
    }
}
