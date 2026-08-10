<?php

namespace Modules\Forms\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormChangeLog;
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
 * registrada en FormChangeLog: un category_id mal cambiado desvía tickets
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

    public function index(): View
    {
        $forms = Form::with('category')->orderBy('name')->get();
        $categories = TicketCategory::where('active', true)->orderBy('order')->get();
        $recentChanges = FormChangeLog::orderByDesc('created_at')->limit(20)->get();

        return view('forms::manage.index', compact('forms', 'categories', 'recentChanges'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $form = Form::create($validated);

        FormChangeLog::record($form->form_key, 'created', $form->id, FormChangeLog::diff([], $validated));

        return redirect()->route('forms.manage.index')
            ->with('success', 'Formulario creado correctamente.');
    }

    public function update(Request $request, Form $form): RedirectResponse
    {
        $before = $form->only(self::AUDITABLE_FIELDS);
        $validated = $this->validated($request, $form);

        $form->update($validated);

        $changes = FormChangeLog::diff($before, $form->only(self::AUDITABLE_FIELDS));

        if ($changes !== []) {
            FormChangeLog::record($form->form_key, 'updated', $form->id, $changes);
        }

        return redirect()->route('forms.manage.index')
            ->with('success', 'Formulario actualizado correctamente.');
    }

    public function toggle(Form $form): RedirectResponse
    {
        $wasActive = $form->active;
        $form->update(['active' => ! $wasActive]);

        FormChangeLog::record(
            $form->form_key,
            $wasActive ? 'deactivated' : 'activated',
            $form->id,
            ['active' => [$wasActive, $form->active]]
        );

        return back()->with('success', 'Estado del formulario actualizado.');
    }

    public function destroy(Form $form): RedirectResponse
    {
        $formKey = $form->form_key;
        $formId = $form->id;

        $form->delete();

        FormChangeLog::record($formKey, 'deleted', $formId);

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
        $forms = Form::whereIn('id', $validated['ids'])->get();

        foreach ($forms as $form) {
            if ($form->active === $active) {
                continue;
            }

            $wasActive = $form->active;
            $form->update(['active' => $active]);

            FormChangeLog::record(
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
        $forms = Form::with('category')->orderBy('name')->get()->map(fn (Form $form) => [
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
                $existing = Form::where('form_key', $formKey)->first();
                $before = $existing?->only(self::AUDITABLE_FIELDS) ?? [];

                $attributes = [
                    'name' => (string) ($row['name'] ?? $formKey),
                    'category_id' => $categoryId,
                    'description' => $row['description'] ?? null,
                    'active' => (bool) ($row['active'] ?? true),
                ];

                $form = Form::updateOrCreate(['form_key' => $formKey], $attributes);

                $changes = FormChangeLog::diff($before, $form->only(self::AUDITABLE_FIELDS));

                if ($changes !== []) {
                    FormChangeLog::record($formKey, 'imported', $form->id, $changes);
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
    private function validated(Request $request, ?Form $form = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
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
