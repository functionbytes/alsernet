<?php

namespace Modules\Mailer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerVariable;
use Modules\Mailer\Traits\AuthorizesMailerActions;

class MailerVariableController extends Controller
{
    use AuthorizesMailerActions;

    /**
     * Display a listing of email variables
     */
    public function index(Request $request): View
    {
        $this->authorizeMailerAction('mailer.variables.view');

        $pageTitle = 'Gestionar Variables de Email';
        $breadcrumb = 'Configuración / Correos / Variables';

        $query = MailerVariable::with('translations')
            ->orderBy('module')
            ->orderBy('category')
            ->orderBy('key');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $variables = $query->paginate(paginationNumber())->withQueryString();

        return view('mailer::variables.index', compact(
            'pageTitle',
            'breadcrumb',
            'variables',
        ));
    }

    /**
     * Show the form for creating a new variable
     */
    public function create(): View
    {
        $this->authorizeMailerAction('mailer.variables.create');

        $pageTitle = 'Crear Variable de Email';
        $breadcrumb = 'Configuración / Correos / Variables / Crear';

        $langs = MailerLang::available()->get();
        $categories = config('mailer-module.category_labels', []);
        $modules = config('mailer-module.modules', []);

        return view('mailer::variables.create', compact(
            'pageTitle',
            'breadcrumb',
            'langs',
            'categories',
            'modules',
        ));
    }

    /**
     * Store a newly created variable in storage
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeMailerAction('mailer.variables.create');

        $validated = $request->validate([
            'key' => 'required|string|unique:mailer_variables,key|regex:/^[A-Z_]+$/',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:system,site,customer,order,document,general',
            'module' => 'required|string|in:core,documents,orders',
            'is_system' => 'boolean',
            'is_enabled' => 'boolean',
            'translations' => 'required|array',
            'translations.*.lang_id' => 'required|exists:langs,id',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'nullable|string',
            'translations.*.value' => 'nullable|string',
        ]);

        $translations = $validated['translations'];
        unset($validated['translations']);

        $variable = MailerVariable::create($validated);

        // Create translations
        foreach ($translations as $translation) {
            $variable->translations()->create($translation);
        }

        return redirect()
            ->route('mailers.variables.index')
            ->with('success', "Variable '{$variable->key}' creada exitosamente.");
    }

    /**
     * Show the form for editing a variable
     */
    public function edit(MailerVariable $variable): View
    {
        $this->authorizeMailerAction('mailer.variables.view');

        $pageTitle = 'Editar Variable de Email';
        $breadcrumb = 'Configuración / Correos / Variables / Editar';

        $langs = MailerLang::available()->get();
        $categories = config('mailer-module.category_labels', []);
        $modules = config('mailer-module.modules', []);

        $variable->load('translations');

        return view('mailer::variables.edit', compact(
            'pageTitle',
            'breadcrumb',
            'variable',
            'langs',
            'categories',
            'modules',
        ));
    }

    /**
     * Update the specified variable in storage
     */
    public function update(Request $request, MailerVariable $variable): RedirectResponse
    {
        $this->authorizeMailerAction('mailer.variables.update');

        // For system variables, key and category are not validated (they're fixed)
        // For custom variables, they must follow strict validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module' => 'required|string|in:core,documents,orders',
            'is_system' => 'boolean',
            'is_enabled' => 'boolean',
            'translations' => 'required|array',
            'translations.*.lang_id' => 'required|exists:langs,id',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'nullable|string',
            'translations.*.value' => 'nullable|string',
        ];

        // Only validate key and category for custom variables
        if (! $variable->is_system) {
            $rules['key'] = [
                'required',
                'string',
                'regex:/^[A-Z_]+$/',
                Rule::unique('mailer_variables', 'key')->ignore($variable->id),
            ];
            $rules['category'] = 'required|string|in:system,site,customer,order,document,general';
        } else {
            // For system variables, just accept the values as-is (they're fixed)
            $rules['key'] = 'required|string';
            $rules['category'] = 'required|string';
        }

        $validated = $request->validate($rules);

        $translations = $validated['translations'];
        unset($validated['translations']);

        $variable->update($validated);

        // Update or create translations
        foreach ($translations as $translation) {
            $variable->translations()->updateOrCreate(
                ['lang_id' => $translation['lang_id']],
                [
                    'name' => $translation['name'],
                    'description' => $translation['description'] ?? null,
                    'value' => $translation['value'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('mailers.variables.index')
            ->with('success', "Variable '{$variable->key}' actualizada exitosamente.");
    }

    /**
     * Remove the specified variable from storage
     */
    public function destroy(MailerVariable $variable): RedirectResponse
    {
        $this->authorizeMailerAction('mailer.variables.delete');

        if ($variable->is_system) {
            return redirect()
                ->route('mailers.variables.index')
                ->with('error', 'No se puede eliminar variables del sistema.');
        }

        $key = $variable->key;
        $variable->delete();

        return redirect()
            ->route('mailers.variables.index')
            ->with('success', "Variable '{$key}' eliminada exitosamente.");
    }

    /**
     * Toggle variable status
     */
    public function toggleStatus(MailerVariable $variable): JsonResponse
    {
        $this->authorizeMailerAction('mailer.variables.update');

        $variable->update(['is_enabled' => ! $variable->is_enabled]);

        $status = $variable->is_enabled ? 'habilitada' : 'deshabilitada';

        return response()->json([
            'success' => true,
            'message' => "Variable '{$variable->key}' {$status}.",
            'is_enabled' => $variable->is_enabled,
        ]);
    }

    /**
     * Get variables for a specific module and category
     */
    public function getByModule(Request $request): JsonResponse
    {
        $this->authorizeMailerAction('mailer.variables.view');

        $module = $request->get('module', 'core');
        $category = $request->get('category');

        $query = MailerVariable::where('module', $module)
            ->enabled();

        if ($category) {
            $query->where('category', $category);
        }

        $variables = $query->orderBy('category')->orderBy('key')->get();

        return response()->json($variables);
    }

    /**
     * Get variables grouped by category for template editor
     */
    public function getGroupedByCategory(Request $request): JsonResponse
    {
        $this->authorizeMailerAction('mailer.variables.view');

        $module = $request->get('module', 'core');

        $variables = MailerVariable::where('module', $module)
            ->orWhere('module', 'core')
            ->enabled()
            ->orderBy('category')
            ->orderBy('key')
            ->get();

        $grouped = [];
        foreach ($variables as $variable) {
            if (! isset($grouped[$variable->category])) {
                $grouped[$variable->category] = [];
            }

            $grouped[$variable->category][] = [
                'key' => $variable->key,
                'name' => $variable->name,
                'description' => $variable->description,
            ];
        }

        return response()->json($grouped);
    }

    /**
     * Get all available variable keys for validation
     */
    public function getAvailableKeys(Request $request): JsonResponse
    {
        $this->authorizeMailerAction('mailer.variables.view');

        $module = $request->get('module', 'core');

        $keys = MailerVariable::where('module', $module)
            ->orWhere('module', 'core')
            ->enabled()
            ->pluck('key')
            ->toArray();

        return response()->json([
            'keys' => $keys,
            'count' => count($keys),
        ]);
    }
}
