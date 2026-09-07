<?php

namespace Modules\Forms\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Forms\Models\AlsernetForm;
use Modules\Forms\Models\AlsernetFormChangeLog;
use Modules\HelpdeskTickets\Models\TicketCategory;

/**
 * CRUD del mapeo form_key -> categoría (tabla helpdesk_forms). Sustituye lo
 * que antes era un array hardcodeado en PHP (FormsReportController::CATEGORY_SLUGS
 * aquí, FormCategoryRegistry del lado alsernetforms/PrestaShop): activar,
 * desactivar o remapear un formulario ya no requiere desplegar código.
 *
 * `form_key` debe coincidir exactamente con el valor que envía alsernetforms
 * en el campo 'type' del payload -- no hay forma de validarlo automáticamente
 * desde aquí porque vive en otro repositorio (Alvarez/PrestaShop).
 *
 * Toda mutación (crear/editar/activar/desactivar/eliminar/importar) queda
 * registrada en AlsernetFormChangeLog: un category_id mal cambiado desvía tickets
 * reales de clientes en silencio, así que el historial de quién-cuándo-qué
 * importa aquí más que en un CRUD de configuración típico.
 */
class FormsManagerController extends Controller
{
    private const AUDITABLE_FIELDS = ['name', 'form_key', 'category_id', 'description', 'active'];

    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    public function index(Request $request): View
    {
        // Los totales se calculan sobre TODOS los formularios, no sobre el
        // resultado filtrado: son el estado del mapeo completo, y colarles el
        // filtro haría que "Activos" cambiara al buscar.
        $stats = [
            'total' => AlsernetForm::count(),
            'active' => AlsernetForm::where('active', true)->count(),
            'inactive' => AlsernetForm::where('active', false)->count(),
            'categories' => AlsernetForm::whereNotNull('category_id')->distinct()->count('category_id'),
            'uncategorised' => AlsernetForm::whereNull('category_id')->count(),
        ];

        $forms = AlsernetForm::with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                // form_key es lo que se busca de verdad cuando llega un envío
                // rechazado desde PrestaShop, así que entra en la búsqueda
                // junto al nombre y la descripción.
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('form_key', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status') === 'active', fn ($q) => $q->where('active', true))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('active', false))
            ->when($request->input('category') === '__none__', fn ($q) => $q->whereNull('category_id'))
            ->when(
                $request->filled('category') && $request->input('category') !== '__none__',
                fn ($q) => $q->where('category_id', $request->input('category'))
            )
            ->orderBy('name')
            ->get();

        $categories = TicketCategory::where('active', true)->orderBy('order')->get();
        $recentChanges = AlsernetFormChangeLog::orderByDesc('created_at')->limit(20)->get();

        return view('forms::manage.index', compact('forms', 'categories', 'recentChanges', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $form = AlsernetForm::create($validated);

        AlsernetFormChangeLog::record($form->form_key, 'created', $form->id, AlsernetFormChangeLog::diff([], $validated));

        return redirect()->route('forms.manage.index')
            ->with('success', 'Formulario creado correctamente.');
    }

    public function update(Request $request, AlsernetForm $form): RedirectResponse
    {
        $before = $form->only(self::AUDITABLE_FIELDS);
        $validated = $this->validated($request, $form);

        $form->update($validated);

        $changes = AlsernetFormChangeLog::diff($before, $form->only(self::AUDITABLE_FIELDS));

        if ($changes !== []) {
            AlsernetFormChangeLog::record($form->form_key, 'updated', $form->id, $changes);
        }

        return redirect()->route('forms.manage.index')
            ->with('success', 'Formulario actualizado correctamente.');
    }

    public function toggle(AlsernetForm $form): RedirectResponse
    {
        $wasActive = $form->active;
        $form->update(['active' => ! $wasActive]);

        AlsernetFormChangeLog::record(
            $form->form_key,
            $wasActive ? 'deactivated' : 'activated',
            $form->id,
            ['active' => [$wasActive, $form->active]]
        );

        return back()->with('success', 'Estado del formulario actualizado.');
    }

    public function destroy(AlsernetForm $form): RedirectResponse
    {
        $formKey = $form->form_key;
        $formId = $form->id;

        // El registro va ANTES del borrado: helpdesk_form_changes.form_id
        // tiene una FK a helpdesk_forms, así que insertarlo después fallaba
        // con "Cannot add or update a child row" y devolvía un 500 — con el
        // formulario ya borrado y sin rastro en el historial. La FK es
        // ON DELETE SET NULL, de modo que al borrar el formulario esta fila
        // se queda con form_id NULL pero conserva form_key y los cambios.
        AlsernetFormChangeLog::record($formKey, 'deleted', $formId);

        $form->delete();

        return redirect()->route('forms.manage.index')
            ->with('success', 'Formulario eliminado. Los envíos que lleguen con ese form_key se rechazarán hasta que se vuelva a crear.');
    }

    /**
     * Activa/desactiva varios formularios a la vez desde el listado.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:helpdesk.helpdesk_forms,id'],
            'bulk_action' => ['required', Rule::in(['activate', 'deactivate'])],
        ]);

        $active = $validated['bulk_action'] === 'activate';
        $forms = AlsernetForm::whereIn('id', $validated['ids'])->get();

        foreach ($forms as $form) {
            if ($form->active === $active) {
                continue;
            }

            $wasActive = $form->active;
            $form->update(['active' => $active]);

            AlsernetFormChangeLog::record(
                $form->form_key,
                $active ? 'activated' : 'deactivated',
                $form->id,
                ['active' => [$wasActive, $active]]
            );
        }

        return back()->with('success', count($forms).' formulario(s) actualizado(s).');
    }

    /**
     * Descarga el catálogo completo en JSON, referenciando la categoría por
     * slug (no id) para que el export sea portable entre entornos (dev/prod
     * tienen ids distintos aunque compartan los mismos slugs sembrados).
     */
    public function exportJson(): JsonResponse
    {
        $forms = AlsernetForm::with('category')->orderBy('name')->get()->map(fn (AlsernetForm $form) => [
            'name' => $form->name,
            'form_key' => $form->form_key,
            'category_slug' => $form->category?->slug,
            'description' => $form->description,
            'active' => $form->active,
        ]);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'forms' => $forms,
        ];

        return response()->json($payload)
            ->header('Content-Disposition', 'attachment; filename="forms-export-'.now()->format('Y-m-d').'.json"');
    }

    /**
     * Importa un catálogo exportado con exportJson(): upsert por form_key,
     * resolviendo category_slug -> category_id. Filas sin form_key o con un
     * category_slug que no existe se omiten (se informan en el mensaje),
     * el resto se aplica dentro de una transacción.
     */
    public function importJson(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:json,txt'],
        ]);

        $decoded = json_decode($request->file('file')->get(), true);
        $rows = is_array($decoded) ? ($decoded['forms'] ?? $decoded) : null;

        if (! is_array($rows)) {
            return back()->with('error', 'El archivo no tiene un JSON válido con una lista de formularios.');
        }

        $imported = 0;
        $skipped = 0;

        DB::connection('helpdesk')->transaction(function () use ($rows, &$imported, &$skipped) {
            foreach ($rows as $row) {
                if (! is_array($row) || empty($row['form_key'])) {
                    $skipped++;

                    continue;
                }

                $categoryId = null;

                if (! empty($row['category_slug'])) {
                    $categoryId = TicketCategory::where('slug', $row['category_slug'])->value('id');

                    if ($categoryId === null) {
                        $skipped++;

                        continue;
                    }
                }

                $formKey = (string) $row['form_key'];
                $existing = AlsernetForm::where('form_key', $formKey)->first();
                $before = $existing?->only(self::AUDITABLE_FIELDS) ?? [];

                $attributes = [
                    'name' => (string) ($row['name'] ?? $formKey),
                    'category_id' => $categoryId,
                    'description' => $row['description'] ?? null,
                    'active' => (bool) ($row['active'] ?? true),
                ];

                $form = AlsernetForm::updateOrCreate(['form_key' => $formKey], $attributes);

                $changes = AlsernetFormChangeLog::diff($before, $form->only(self::AUDITABLE_FIELDS));

                if ($changes !== []) {
                    AlsernetFormChangeLog::record($formKey, 'imported', $form->id, $changes);
                }

                $imported++;
            }
        });

        $message = "Importados {$imported} formulario(s).";

        if ($skipped > 0) {
            $message .= " {$skipped} fila(s) omitida(s) (sin form_key o categoría desconocida).";
        }

        return redirect()->route('forms.manage.index')->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?AlsernetForm $form = null): array
    {
        $validated = $request->validate([
            // max:191, no 255: la columna es varchar(191) y con sql_mode
            // STRICT_TRANS_TABLES un nombre más largo pasaba la validación y
            // reventaba con "Data too long for column 'name'" (500).
            'name' => ['required', 'string', 'max:191'],
            'form_key' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('helpdesk.helpdesk_forms', 'form_key')->ignore($form?->id),
            ],
            'category_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_ticket_categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['required', 'boolean'],
        ]);

        $validated['active'] = $request->boolean('active');

        return $validated;
    }
}
