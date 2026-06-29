<?php

namespace Modules\Forms\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Forms\Http\Requests\QuickStoreFormRequest;
use Modules\Forms\Http\Resources\FormSubmissionResource;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Services\FormService;

class FormApiController extends Controller
{
    public function index(Form $form): AnonymousResourceCollection
    {
        $submissions = $form->submissions()
            ->with('values')
            ->latest()
            ->paginate(20);

        return FormSubmissionResource::collection($submissions);
    }

    /**
     * Lista simple de forms activos para el picker del Visual Editor.
     */
    public function picker(Request $request): JsonResponse
    {
        $this->authorize('Forms.forms.index');

        $search = (string) $request->query('q', '');
        $limit = min((int) $request->query('limit', 50), 200);

        $forms = Form::query()
            ->select(['id', 'name', 'slug'])
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q
                ->where(fn ($sub) => $sub
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')))
            ->orderBy('name')
            ->withCount('submissions')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $forms->map(fn (Form $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'slug' => $f->slug,
                'submissions_count' => $f->submissions_count,
                'text' => "{$f->name} (#{$f->id})",
            ])->all(),
        ]);
    }

    /**
     * Meta del form para el inspector del VE.
     */
    public function meta(Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.index');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $form->id,
                'name' => $form->name,
                'slug' => $form->slug,
                'is_active' => (bool) $form->is_active,
                'is_expired' => $form->is_expired,
                'submissions_count' => $form->getSubmissionsCount(),
                'unread_count' => $form->getUnreadCount(),
                'fields_count' => $form->fields()->count(),
                'last_submission_at' => $form->submissions()->max('created_at'),
                'edit_url' => route('settings.forms.edit', $form),
                'inbox_url' => route('forms.inbox.index').'?form_id='.$form->id,
                'analytics_url' => route('settings.forms.analytics', $form),
            ],
        ]);
    }

    /**
     * Crea rápidamente un form desde el VE a partir de una plantilla.
     */
    public function quickStore(QuickStoreFormRequest $request): JsonResponse
    {
        $service = app(FormService::class);
        $data = $request->validated();

        $templates = (array) config('forms.template_library', []);
        $template = $templates[$data['template']] ?? null;

        $form = DB::transaction(function () use ($data, $template, $service) {
            $name = $data['name'];
            $form = Form::query()->create([
                'name' => $name,
                'slug' => $service->generateUniqueSlug($name),
                'is_active' => true,
                'success_message' => config('forms.default_success_message'),
            ]);

            foreach (($template['fields'] ?? []) as $idx => $f) {
                $form->fields()->create(array_merge($f, ['sort_order' => $idx]));
            }

            $service->createInitialVersion($form);

            return $form;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $form->id,
                'slug' => $form->slug,
                'name' => $form->name,
                'shortcode' => $form->shortcode_tag,
                'edit_url' => route('settings.forms.edit', $form),
            ],
        ], 201);
    }

    public function previewHtml(Request $request, Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.index');

        $data = $request->validate([
            'display' => ['nullable', 'in:inline,popup'],
            'columns' => ['nullable', 'integer', 'in:1,2,3'],
            'theme' => ['nullable', 'string', 'max:50'],
            'show_title' => ['nullable', 'boolean'],
        ]);

        $form->load(['fields' => fn ($q) => $q->visible()->ordered()]);

        $shortcodeConfig = [
            'display' => $data['display'] ?? 'inline',
            'columns' => (int) ($data['columns'] ?? 1),
            'show_title' => $data['show_title'] ?? true,
            'theme' => $data['theme'] ?? $form->theme,
            'button_text' => null,
        ];

        $html = view('forms::visual-editor.embed-preview', [
            'form' => $form,
            'shortcodeConfig' => $shortcodeConfig,
        ])->render();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $form->id,
                'slug' => $form->slug,
                'name' => $form->name,
                'shortcode' => $form->shortcode_tag,
                'fields_count' => $form->fields->count(),
                'html' => $html,
            ],
        ]);
    }

    public function show(Form $form, FormSubmission $submission): FormSubmissionResource
    {
        $submission->load(['values', 'assignedTo']);

        return new FormSubmissionResource($submission);
    }

    public function destroy(Form $form, FormSubmission $submission): JsonResponse
    {
        $submission->delete();

        return response()->json(['message' => 'Envío eliminado correctamente.']);
    }

    public function stats(Form $form): JsonResponse
    {
        return response()->json([
            'form_id' => $form->id,
            'form_name' => $form->name,
            'total_submissions' => $form->submissions()->count(),
            'unread' => $form->submissions()->unread()->count(),
            'spam' => $form->submissions()->spam()->count(),
            'by_status' => $form->submissions()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'last_7_days' => $form->submissions()
                ->where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),
            'avg_time_to_complete' => $form->submissions()
                ->whereNotNull('time_to_complete')
                ->avg('time_to_complete'),
        ]);
    }
}
