<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Forms\Http\Requests\StoreFormRequest;
use Modules\Forms\Http\Requests\UpdateFormRequest;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormCategory;
use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormVersion;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FormController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('Forms.forms.index');

        $forms = Form::query()
            ->with(['category'])
            ->withCount(['submissions'])
            ->when($request->filled('search'), fn ($q) => $q
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('slug', 'like', '%'.$request->search.'%'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = DB::table('forms')->selectRaw('
            COUNT(*) as total_forms,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_forms
        ')->first();

        $submissionStats = DB::table('form_submissions')->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
        ')->first();

        $totalSubmissions = (int) $submissionStats->total;
        $submissionsToday = (int) $submissionStats->today;

        $categories = FormCategory::query()->active()->ordered()->get();

        return view('forms::forms.index', compact('forms', 'stats', 'totalSubmissions', 'submissionsToday', 'categories'));
    }

    public function create(): View
    {
        $this->authorize('Forms.forms.create');

        $categories = FormCategory::query()->active()->ordered()->get();

        return view('forms::forms.create', compact('categories'));
    }

    public function store(StoreFormRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['success_message'] = $data['success_message'] ?? config('forms.default_success_message');

        $form = Form::query()->create($data);

        $this->createInitialVersion($form);

        session()->flash('success', 'Formulario creado correctamente. Ahora puedes añadir campos.');

        return redirect()->route('settings.forms.edit', $form);
    }

    public function show(Form $form): View
    {
        $this->authorize('Forms.forms.index');

        $form->load(['category', 'fields' => fn ($q) => $q->ordered(), 'submissions']);

        return view('forms::forms.show', compact('form'));
    }

    public function edit(Form $form): View
    {
        $this->authorize('Forms.forms.edit');

        $form->load(['category', 'fields' => fn ($q) => $q->ordered()]);

        $categories = FormCategory::query()->active()->ordered()->get();

        return view('forms::forms.edit', compact('form', 'categories'));
    }

    public function update(UpdateFormRequest $request, Form $form): RedirectResponse
    {
        $data = $request->validated();

        $nameChanged = $form->name !== $data['name'];
        $hasNoSubmissions = $form->submissions()->doesntExist();

        if ($nameChanged && $hasNoSubmissions) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $form->id);
        }

        $form->update($data);

        session()->flash('success', 'Formulario actualizado correctamente.');

        return redirect()->route('settings.forms.edit', $form);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $forms = Form::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($forms as $form) {
            match ($validated['action']) {
                'activate' => $form->update(['is_active' => true]),
                'deactivate' => $form->update(['is_active' => false]),
                'delete' => $form->delete(),
            };
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function destroy(Form $form): RedirectResponse
    {
        $this->authorize('Forms.forms.delete');

        if ($form->submissions()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar el formulario porque tiene envíos activos.');
        }

        $form->delete();

        session()->flash('success', 'Formulario eliminado correctamente.');

        return redirect()->route('settings.forms.index');
    }

    public function clone(Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.create');

        try {
            $cloned = DB::transaction(function () use ($form) {
                $newForm = $form->replicate(['slug']);
                $newForm->name = 'Copia de '.$form->name;
                $newForm->slug = $this->generateUniqueSlug($newForm->name);
                $newForm->save();

                $form->fields()->ordered()->get()->each(function (FormField $field) use ($newForm) {
                    $clonedField = $field->replicate(['form_id']);
                    $clonedField->form_id = $newForm->id;
                    $clonedField->save();
                });

                $this->createInitialVersion($newForm);

                return $newForm;
            });

            return response()->json([
                'success' => true,
                'message' => 'Formulario clonado correctamente.',
                'redirect' => route('settings.forms.edit', $cloned),
            ]);
        } catch (\Throwable $e) {
            Log::error('Form clone failed', ['form_id' => $form->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al clonar el formulario. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    public function preview(Form $form): View
    {
        $this->authorize('Forms.forms.index');

        $form->load(['fields' => fn ($q) => $q->ordered()->visible(), 'category']);

        $previewToken = hash_hmac('sha256', (string) $form->id, config('app.key'));

        return view('forms::forms.preview', compact('form', 'previewToken'));
    }

    public function previewPublic(Request $request, Form $form, string $token): View|Response
    {
        $expected = hash_hmac('sha256', (string) $form->id, config('app.key'));

        if (! hash_equals($expected, $token)) {
            abort(403, 'Token de preview inválido.');
        }

        $form->load(['fields' => fn ($q) => $q->visible()->ordered(), 'category']);

        return view('forms::public.preview', compact('form'));
    }

    public function qrcode(Form $form): Response
    {
        $this->authorize('Forms.forms.index');

        $url = route('forms.public.submit', ['slug' => $form->slug]);

        $image = QrCode::format('png')->size(300)->generate($url);

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qrcode-'.$form->slug.'.png"',
        ]);
    }

    public function exportJson(Form $form): Response
    {
        $this->authorize('Forms.forms.index');

        $payload = [
            'form' => $form->toArray(),
            'fields' => $form->fields()->ordered()->get()->toArray(),
        ];

        return response(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$form->slug.'.json"',
        ]);
    }

    public function importJson(Request $request): RedirectResponse
    {
        $this->authorize('Forms.forms.create');

        $request->validate([
            'file' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:2048'],
        ]);

        try {
            $content = file_get_contents($request->file('file')->getRealPath());
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($data['form'])) {
                return redirect()->back()->with('error', 'Formato de archivo JSON inválido.');
            }

            $form = DB::transaction(function () use ($data) {
                $formData = collect($data['form'])->only([
                    'name', 'category_id', 'description', 'success_message',
                    'redirect_url', 'submit_button_text', 'is_active',
                    'allow_multiple', 'honeypot_enabled', 'is_multi_step',
                ]);

                $formData['name'] = $formData['name'].' (importado)';
                $formData['slug'] = $this->generateUniqueSlug($formData['name']);

                $form = Form::query()->create($formData->all());

                foreach (($data['fields'] ?? []) as $sortOrder => $fieldData) {
                    $form->fields()->create(
                        collect($fieldData)->only([
                            'type', 'name', 'label', 'placeholder', 'help_text',
                            'default_value', 'options', 'validation_rules',
                            'is_required', 'is_visible', 'sort_order', 'step_number',
                            'width', 'min_value', 'max_value', 'step_value',
                            'show_char_counter', 'likert_rows',
                        ])->merge(['sort_order' => $sortOrder])->all()
                    );
                }

                $this->createInitialVersion($form);

                return $form;
            });

            session()->flash('success', 'Formulario importado correctamente.');

            return redirect()->route('settings.forms.edit', $form);
        } catch (\JsonException $e) {
            return redirect()->back()->with('error', 'El archivo no contiene JSON válido.');
        } catch (\Throwable $e) {
            Log::error('Form import failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Error al importar el formulario. Por favor, verifica el archivo e inténtalo de nuevo.');
        }
    }

    public function analytics(Form $form): View
    {
        $this->authorize('Forms.analytics.index');

        $form->load(['category']);

        $submissionsByDay = DB::table('form_submissions')
            ->where('form_id', $form->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $submissionsByStatus = DB::table('form_submissions')
            ->where('form_id', $form->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $submissionsByHour = DB::table('form_submissions')
            ->where('form_id', $form->id)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $totalSubmissions = $form->submissions()->count();
        $abandonCount = DB::table('form_abandon_tracking')
            ->where('form_id', $form->id)
            ->count();

        $conversionRate = ($totalSubmissions + $abandonCount) > 0
            ? round(($totalSubmissions / ($totalSubmissions + $abandonCount)) * 100, 1)
            : 0;

        return view('forms::settings.forms.analytics', compact(
            'form',
            'submissionsByDay',
            'submissionsByStatus',
            'submissionsByHour',
            'totalSubmissions',
            'abandonCount',
            'conversionRate',
        ));
    }

    public function updateProtection(Request $request, Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $data = $request->validate([
            'honeypot_enabled' => ['sometimes', 'boolean'],
            'captcha_enabled' => ['sometimes', 'boolean'],
        ]);

        $form->update($data);

        return response()->json(['success' => true]);
    }

    public function addStep(Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $steps = $form->steps_config ?? [];
        $nextNumber = count($steps) + 1;
        $steps[] = ['number' => $nextNumber, 'title' => "Paso {$nextNumber}", 'description' => null];

        $form->update(['steps_config' => $steps]);

        return response()->json(['success' => true, 'step' => end($steps), 'count' => count($steps)]);
    }

    public function removeStep(Request $request, Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $request->validate(['step_number' => ['required', 'integer', 'min:1']]);

        $steps = collect($form->steps_config ?? [])
            ->reject(fn ($s) => $s['number'] === $request->step_number)
            ->values()
            ->map(fn ($s, $i) => array_merge($s, ['number' => $i + 1]))
            ->all();

        $form->update(['steps_config' => $steps]);

        return response()->json(['success' => true, 'count' => count($steps)]);
    }

    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Form::query()
            ->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function createInitialVersion(Form $form): void
    {
        $lastVersion = FormVersion::query()
            ->where('form_id', $form->id)
            ->max('version_number') ?? 0;

        FormVersion::query()->create([
            'form_id' => $form->id,
            'version_number' => $lastVersion + 1,
            'form_snapshot' => $form->toArray(),
            'fields_snapshot' => $form->fields()->ordered()->get()->toArray(),
            'created_by' => auth()->id(),
        ]);
    }
}
