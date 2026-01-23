<?php

namespace Modules\Mailer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Models\MailerLayoutLang;
use Modules\Mailer\Traits\AuthorizesMailerActions;

class MailerComponentController extends Controller
{
    use AuthorizesMailerActions;

    /**
     * Listar todos los componentes de email (header, footer, etc.)
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $langId = $request->input('lang_id', 1); // Default to first language

        $query = MailerLayout::where('group_name', 'mail_templates');

        // Búsqueda - now search in translations
        if ($search) {
            $query->where(function ($q) use ($search, $langId) {
                $q->where('alias', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhereHas('translations', function ($tq) use ($search, $langId) {
                        $tq->where('lang_id', $langId)
                            ->where('subject', 'like', '%'.$search.'%');
                    });
            });
        }

        // Filtrar por tipo
        if ($type) {
            $query->where('type', $type);
        }

        // Eager load all translations to calculate completion status
        $query->with(['translations' => function ($q) {
            $q->with('lang')->orderBy('lang_id');
        }]);

        $components = $query->orderByDesc('updated_at')->paginate(15);

        // Obtener tipos únicos para filtro
        $types = MailerLayout::where('group_name', 'mail_templates')
            ->distinct('type')
            ->pluck('type')
            ->toArray();

        // Obtener idiomas disponibles
        $langs = MailerLang::available()->get();

        return view('mailer::components.index', [
            'components' => $components,
            'search' => $search,
            'type' => $type,
            'langId' => $langId,
            'types' => $types,
            'langs' => $langs,
        ]);
    }

    /**
     * Mostrar formulario para crear nuevo componente
     */
    public function create(Request $request)
    {
        // Obtener idioma del request (default: primer idioma disponible)
        $langId = $request->input('lang_id');
        if (! $langId) {
            $defaultLang = MailerLang::available()->first();
            $langId = $defaultLang?->id;
        }

        // Obtener idiomas disponibles
        $langs = MailerLang::available()->get();

        return view('mailer::components.create', [
            'langId' => $langId,
            'langs' => $langs,
        ]);
    }

    /**
     * Guardar nuevo componente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'alias' => 'required|string',
            'subject' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'type' => 'required|string|in:partial,layout,component',
            'content' => 'required|string',
            'lang_id' => 'required|exists:langs,id',
            'is_protected' => 'nullable|boolean',
        ]);

        try {
            // Validar que no exista ya un componente con este alias
            $existing = MailerLayout::where('alias', $validated['alias'])
                ->where('group_name', 'mail_templates')
                ->first();

            if ($existing) {
                return redirect()
                    ->back()
                    ->with('error', 'Ya existe un componente con este alias')
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

            // Crear el layout (sin subject/content, solo metadata)
            $layout = MailerLayout::create([
                'alias' => $validated['alias'],
                'code' => $validated['code'],
                'type' => $validated['type'],
                'group_name' => 'mail_templates',
                'is_protected' => $validated['is_protected'] ?? false,
            ]);

            // Crear traducciones para todos los idiomas
            foreach ($allLangs as $lang) {
                MailerLayoutLang::create([
                    'layout_id' => $layout->id,
                    'lang_id' => $lang->id,
                    'subject' => $validated['subject'],
                    'content' => $validated['content'],
                ]);
            }

            return redirect()
                ->route('mailers.components.edit', [
                    'uid' => $layout->uid,
                    'lang_id' => $validated['lang_id'],
                ])
                ->with('success', "Componente '{$validated['subject']}' creado exitosamente para todos los idiomas (".count($allLangs).' versiones)');
        } catch (\Exception $e) {
            \Log::error('Error creating email component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated ?? [],
            ]);

            return redirect()
                ->back()
                ->with('error', 'Error al crear el componente: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar formulario para editar componente
     */
    public function edit(Request $request, $uid)
    {
        $layout = MailerLayout::where('uid', $uid)->firstOrFail();

        // Verificar que sea un componente de email
        if ($layout->group_name !== 'mail_templates') {
            abort(404);
        }

        // Obtener idioma actual (del request o default a 1)
        $langId = $request->input('lang_id', 1);

        // Obtener la traducción para el idioma actual
        $translation = $layout->translations()->where('lang_id', $langId)->first();

        // Si no existe traducción para este idioma, crear y guardar una vacía
        if (! $translation) {
            $translation = MailerLayoutLang::create([
                'layout_id' => $layout->id,
                'lang_id' => $langId,
                'subject' => '',
                'content' => '',
            ]);
        }

        // Obtener idiomas disponibles
        $langs = MailerLang::available()->get();

        // Obtener otras traducciones para este layout
        $otherTranslations = $layout->translations()
            ->where('lang_id', '!=', $langId)
            ->with('lang')
            ->get();

        return view('mailer::components.edit', [
            'component' => $layout, // Keep 'component' for backwards compatibility with view
            'layout' => $layout,
            'translation' => $translation,
            'currentLangId' => $langId,
            'langs' => $langs,
            'otherLangs' => $otherTranslations, // Keep 'otherLangs' for backwards compatibility
            'otherTranslations' => $otherTranslations,
        ]);
    }

    /**
     * Actualizar componente
     */
    public function update(Request $request, $uid)
    {
        $layout = MailerLayout::where('uid', $uid)->firstOrFail();

        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',  // Allow empty strings and null
            'type' => 'required|string|in:partial,layout,component',
            'lang_id' => 'required|exists:langs,id',
            'is_protected' => 'nullable|boolean',
        ]);

        try {
            \Log::info('MailerComponentController::update', [
                'uid' => $uid,
                'lang_id' => $validated['lang_id'],
                'subject_length' => strlen($validated['subject'] ?? ''),
                'content_length' => strlen($validated['content'] ?? ''),
                'has_content' => ! empty($validated['content']),
            ]);

            // Actualizar el tipo y protección del layout (metadata)
            $layout->update([
                'type' => $validated['type'],
                'is_protected' => $validated['is_protected'] ?? false,
            ]);

            // Obtener la traducción para el idioma actual (sin fallback)
            $translation = $layout->translations()->where('lang_id', $validated['lang_id'])->first();

            if ($translation) {
                // Actualizar traducción existente - Explicitly set content even if empty
                $translation->update([
                    'subject' => $validated['subject'] ?? '',
                    'content' => $validated['content'] ?? '',  // Force update even if empty
                ]);

                \Log::info('Translation updated', [
                    'translation_id' => $translation->id,
                    'new_content_length' => strlen($translation->content ?? ''),
                ]);
            } else {
                // Crear nueva traducción si no existe
                $translation = MailerLayoutLang::create([
                    'layout_id' => $layout->id,
                    'lang_id' => $validated['lang_id'],
                    'subject' => $validated['subject'] ?? '',
                    'content' => $validated['content'] ?? '',
                ]);

                \Log::info('Translation created', [
                    'translation_id' => $translation->id,
                    'content_length' => strlen($translation->content ?? ''),
                ]);
            }

            // Siempre redirigir a la misma URL para mantener consistencia
            return redirect()
                ->route('mailers.components.edit', [
                    'uid' => $layout->uid,
                    'lang_id' => $validated['lang_id'],
                ])
                ->with('success', 'Componente actualizado exitosamente');
        } catch (\Exception $e) {
            \Log::error('Error updating component', [
                'uid' => $uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Error al actualizar: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Vista previa del componente
     */
    public function preview(Request $request, $uid)
    {
        $layout = MailerLayout::where('uid', $uid)->firstOrFail();

        // Obtener idioma actual (del request o default a 1)
        $langId = $request->input('lang_id', 1);

        // Obtener la traducción para el idioma actual
        $translation = $layout->translate($langId);

        if (! $translation) {
            abort(404, 'No existe traducción para este idioma');
        }

        // Reemplazar variables con datos de ejemplo
        $html = $this->replaceExampleVariables($translation->content);

        return view('mailer::components.preview', [
            'component' => $layout, // Keep for backwards compatibility
            'layout' => $layout,
            'translation' => $translation,
            'html' => $html,
        ]);
    }

    /**
     * Vista previa AJAX
     */
    public function previewAjax(Request $request, $uid)
    {
        $layout = MailerLayout::where('uid', $uid)->firstOrFail();

        // Obtener idioma actual (del request o default a 1)
        $langId = $request->input('lang_id', 1);

        // Obtener la traducción para el idioma actual
        $translation = $layout->translate($langId);

        if (! $translation) {
            return response()->json([
                'success' => false,
                'error' => 'No existe traducción para este idioma',
            ], 404);
        }

        // Reemplazar variables con datos de ejemplo
        $html = $this->replaceExampleVariables($translation->content);

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * Eliminar componente
     */
    public function destroy($uid)
    {
        $layout = MailerLayout::where('uid', $uid)->firstOrFail();

        // Verificar si el componente está protegido
        if ($layout->is_protected) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar un componente protegido. Desactiva la protección primero si deseas eliminarlo.');
        }

        // Verificar que no sea un componente crítico del sistema (legacy check)
        $criticalComponents = ['mail_template_header', 'mail_template_footer', 'mail_template_wrapper'];

        if (in_array($layout->alias, $criticalComponents)) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar un componente crítico del sistema. Puedes editarlo en su lugar.');
        }

        try {
            // Get name from any translation for the success message
            $name = $layout->translate()?->subject ?? $layout->alias;

            // Delete all translations first (if not using cascade)
            $layout->translations()->delete();

            // Delete the layout
            $layout->delete();

            return redirect()
                ->route('mailers.components.index')
                ->with('success', "Componente '{$name}' eliminado exitosamente");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar: '.$e->getMessage());
        }
    }

    /**
     * Duplicar componente
     */
    public function duplicate(Request $request, $uid)
    {
        $layout = MailerLayout::where('uid', $uid)->firstOrFail();

        try {
            // Replicar el layout (sin translations)
            $newLayout = $layout->replicate();
            $newLayout->alias = $layout->alias.'_copy_'.time();
            $newLayout->code = $layout->code.'_copy';
            $newLayout->save();

            // Duplicar todas las traducciones
            foreach ($layout->translations as $translation) {
                $newTranslation = $translation->replicate();
                $newTranslation->layout_id = $newLayout->id;
                $newTranslation->subject = $translation->subject.' (Copia)';
                $newTranslation->save();
            }

            // Get the current language from request to redirect properly
            $langId = $request->input('lang_id', 1);

            return redirect()
                ->route('mailers.components.edit', [
                    'uid' => $newLayout->uid,
                    'lang_id' => $langId,
                ])
                ->with('success', 'Componente duplicado exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al duplicar: '.$e->getMessage());
        }
    }

    /**
     * Reemplazar variables con valores de ejemplo
     */
    private function replaceExampleVariables(string $content): string
    {
        $variables = [
            'LOGO_URL' => config('app.url').'/images/logo.png',
            'SITE_NAME' => config('app.name', 'Alsernet'),
            'SITE_URL' => config('app.url'),
            'SITE_EMAIL' => 'soporte@Alsernet.com',
            'COMPANY_ADDRESS' => 'Calle Principal 123',
            'COMPANY_CITY' => 'Ciudad',
            'COMPANY_COUNTRY' => 'Colombia',
            'COMPANY_PHONE' => '+57 300 123 4567',
            'COMPANY_EMAIL' => 'info@Alsernet.com',
            'CURRENT_YEAR' => date('Y'),
            'CURRENT_MONTH' => date('m'),
            'CURRENT_DAY' => date('d'),
            'RECIPIENT_EMAIL' => 'ejemplo@email.com',
            'mail_SUBJECT' => 'Email de Ejemplo',
            'CUSTOMER_NAME' => 'Juan García',
            'RESET_LINK' => 'https://example.com/reset-password',
        ];

        foreach ($variables as $key => $value) {
            $content = str_replace('{'.$key.'}', $value, $content);
        }

        return $content;
    }

    /**
     * Listar variables disponibles para componentes (desde la base de datos)
     */
    public function variables()
    {
        try {
            // Obtener todas las variables habilitadas desde la base de datos
            $dbVariables = \Modules\Mailer\Models\MailerVariable::where('is_enabled', true)
                ->orderBy('category')
                ->orderBy('module')
                ->orderBy('key')
                ->get();

            // Agrupar variables por categoría
            $grouped = $dbVariables->groupBy('category');

            $variables = [];
            foreach ($grouped as $category => $items) {
                $categoryLabel = ucfirst($category);
                $categoryLabels = [
                    'system' => 'Sistema',
                    'customer' => 'Cliente',
                    'order' => 'Pedido',
                    'document' => 'Documento',
                    'general' => 'General',
                ];

                $variables[] = [
                    'group' => $categoryLabels[$category] ?? $categoryLabel,
                    'items' => $items->map(function ($variable) {
                        return [
                            'name' => $variable->key,
                            'description' => $variable->description ?? $variable->name,
                        ];
                    })->toArray(),
                ];
            }

            return response()->json([
                'success' => true,
                'variables' => $variables,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading variables for components: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar variables',
                'variables' => [],
            ], 500);
        }
    }
}
