<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Forms\Http\Requests\BulkActionFormRequest;
use Modules\Forms\Http\Requests\ImportFormRequest;
use Modules\Forms\Http\Requests\RemoveFormStepRequest;
use Modules\Forms\Http\Requests\StoreFormRequest;
use Modules\Forms\Http\Requests\UpdateFormProtectionRequest;
use Modules\Forms\Http\Requests\UpdateFormRequest;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormCategory;
use Modules\Forms\Models\FormFieldTypeSetting;
use Modules\Forms\Services\FormAnalyticsService;
use Modules\Forms\Services\FormService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FormController extends Controller
{
    public function __construct(
        private readonly FormService $service,
        private readonly FormAnalyticsService $analyticsService,
    ) {}

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
        $data['slug'] = $this->service->generateUniqueSlug($data['name']);
        $data['success_message'] = $data['success_message'] ?? config('forms.default_success_message');

        $form = Form::query()->create($data);

        $this->service->createInitialVersion($form);

        session()->flash('success', __('forms::messages.crud.form_created'));

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
        $previewToken = hash_hmac('sha256', (string) $form->id, config('app.key'));
        $fieldTypeSettings = FormFieldTypeSetting::query()->ordered()->get()->groupBy('group_name');

        return view('forms::forms.edit', compact('form', 'categories', 'previewToken', 'fieldTypeSettings'));
    }

    public function update(UpdateFormRequest $request, Form $form): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $nameChanged = $form->name !== $data['name'];
        $hasNoSubmissions = $form->submissions()->doesntExist();

        if ($nameChanged && $hasNoSubmissions) {
            $data['slug'] = $this->service->generateUniqueSlug($data['name'], $form->id);
        }

        $form->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'name' => $form->fresh()->name,
                'slug' => $form->fresh()->slug,
            ]);
        }

        session()->flash('success', __('forms::messages.crud.form_updated'));

        return redirect()->route('settings.forms.edit', $form);
    }

    public function bulkAction(BulkActionFormRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $count = DB::transaction(function () use ($validated): int {
            $forms = Form::query()->whereIn('id', $validated['ids'])->get();
            $processed = 0;

            foreach ($forms as $form) {
                match ($validated['action']) {
                    'activate' => $form->update(['is_active' => true]),
                    'deactivate' => $form->update(['is_active' => false]),
                    'delete' => $form->delete(),
                };
                $processed++;
            }

            return $processed;
        });

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function destroy(Form $form): RedirectResponse
    {
        $this->authorize('Forms.forms.delete');

        if ($form->submissions()->exists()) {
            return redirect()->back()
                ->with('error', __('forms::messages.crud.form_delete_has_submissions'));
        }

        $form->delete();

        session()->flash('success', __('forms::messages.crud.form_deleted'));

        return redirect()->route('settings.forms.index');
    }

    public function clone(Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.create');

        try {
            $cloned = $this->service->cloneForm($form);

            return response()->json([
                'success' => true,
                'message' => __('forms::messages.crud.form_cloned'),
                'redirect' => route('settings.forms.edit', $cloned),
            ]);
        } catch (\Throwable $e) {
            Log::error('Form clone failed', ['form_id' => $form->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('forms::messages.crud.form_clone_error'),
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
            abort(403, __('forms::messages.common.invalid_preview_token'));
        }

        $form->load(['fields' => fn ($q) => $q->visible()->ordered(), 'category']);

        return view('forms::public.preview', compact('form'));
    }

    public function qrcode(Form $form): Response
    {
        $this->authorize('Forms.forms.index');

        if (! class_exists(QrCode::class)) {
            abort(501, __('forms::messages.common.qr_package_missing'));
        }

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

    public function importJson(ImportFormRequest $request): RedirectResponse
    {
        $maxFields = (int) config('forms.import.max_fields', 100);
        $maxFileBytes = (int) config('forms.import.max_file_bytes', 512 * 1024);

        try {
            $realPath = $request->file('file')->getRealPath();

            if (filesize($realPath) > $maxFileBytes) {
                return redirect()->back()->with('error', 'El archivo excede el tamaño máximo permitido.');
            }

            $content = file_get_contents($realPath);
            $data = json_decode($content, true, 32, JSON_THROW_ON_ERROR);

            if (! is_array($data) || ! isset($data['form']) || ! is_array($data['form'])) {
                return redirect()->back()->with('error', __('forms::messages.crud.import_invalid_format'));
            }

            $incomingFields = $data['fields'] ?? [];

            if (! is_array($incomingFields)) {
                return redirect()->back()->with('error', __('forms::messages.crud.import_invalid_fields_section'));
            }

            if (count($incomingFields) > $maxFields) {
                return redirect()->back()->with('error', __('forms::messages.crud.import_too_many_fields', ['max' => $maxFields]));
            }

            $form = DB::transaction(function () use ($data, $incomingFields) {
                $formData = collect($data['form'])->only([
                    'name', 'category_id', 'description', 'success_message',
                    'redirect_url', 'submit_button_text', 'is_active',
                    'allow_multiple', 'honeypot_enabled', 'is_multi_step',
                ]);

                $formData['name'] = $formData['name'].' (importado)';
                $formData['slug'] = $this->service->generateUniqueSlug($formData['name']);

                $form = Form::query()->create($formData->all());

                foreach ($incomingFields as $sortOrder => $fieldData) {
                    if (! is_array($fieldData)) {
                        continue;
                    }

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

                $this->service->createInitialVersion($form);

                return $form;
            });

            session()->flash('success', __('forms::messages.crud.form_imported'));

            return redirect()->route('settings.forms.edit', $form);
        } catch (\JsonException) {
            return redirect()->back()->with('error', __('forms::messages.crud.import_invalid_json'));
        } catch (\Throwable $e) {
            Log::error('Form import failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', __('forms::messages.crud.import_generic_error'));
        }
    }

    public function htmlSource(Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $form->load(['fields' => fn ($q) => $q->ordered()->visible()]);

        $formId = 'form-preview-'.$form->id;
        $floatingLabel = $form->floating_label ?? false;

        $html = view('forms::settings.forms.partials.estructura', [
            'form' => $form,
            'formId' => $formId,
            'floatingLabel' => $floatingLabel,
        ])->render();

        $html = $this->service->cleanStructureHtml($html);

        return response()->json(['html' => $html]);
    }

    public function analytics(Form $form): View
    {
        $this->authorize('Forms.analytics.index');

        $form->load(['category']);
        $data = $this->analyticsService->overview($form);

        return view('forms::settings.forms.analytics', [
            'form' => $form,
            'submissionsByDay' => $data['submissions_by_day'],
            'submissionsByStatus' => $data['submissions_by_status'],
            'submissionsByHour' => $data['submissions_by_hour'],
            'totalSubmissions' => $data['total_submissions'],
            'abandonCount' => $data['abandon_count'],
            'conversionRate' => $data['conversion_rate'],
            'topSourcePages' => $data['top_source_pages'] ?? collect(),
        ]);
    }

    public function updateProtection(UpdateFormProtectionRequest $request, Form $form): JsonResponse
    {
        $form->update($request->validated());

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

    public function removeStep(RemoveFormStepRequest $request, Form $form): JsonResponse
    {
        $stepNumber = (int) $request->validated()['step_number'];

        $steps = collect($form->steps_config ?? [])
            ->reject(fn ($s) => $s['number'] === $stepNumber)
            ->values()
            ->map(fn ($s, $i) => array_merge($s, ['number' => $i + 1]))
            ->all();

        $form->update(['steps_config' => $steps]);

        return response()->json(['success' => true, 'count' => count($steps)]);
    }
}
