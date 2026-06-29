<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\SaveContentDto;
use Modules\Campaign\Http\Requests\EmailTemplate\CopyEmailTemplateRequest;
use Modules\Campaign\Http\Requests\EmailTemplate\CreateEmailTemplateRequest;
use Modules\Campaign\Http\Requests\EmailTemplate\DeleteEmailTemplatesRequest;
use Modules\Campaign\Http\Requests\EmailTemplate\RenameEmailTemplateRequest;
use Modules\Campaign\Models\Template\SystemEmailTemplate;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Models\Template\TemplateCategory;
use Modules\Campaign\Services\SystemEmailTemplateService;
use Modules\Campaign\Services\TemplateService;
use Symfony\Component\HttpFoundation\Response;

/**
 * SystemEmailTemplateController — orquestación fina (FormRequest → DTO →
 * Service → Response). Espejo de PageTemplateController para plantillas de
 * email del admin (builderTemplateKind='email'). Reutiliza los endpoints
 * genéricos del builder (builder_edit, change_template, asset_upload, export,
 * theme_asset, thumb) del grupo page_templates, que operan sobre Template.
 */
class SystemEmailTemplateController extends Controller
{
    public function __construct(
        private readonly SystemEmailTemplateService $service,
    ) {}

    private function denied()
    {
        abort(Response::HTTP_FORBIDDEN);
    }

    // ── READ ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        if (Gate::denies('read', new SystemEmailTemplate)) {
            $this->denied();
        }

        $baseCategory = TemplateCategory::whereName('Base')->first();
        $extendedCategory = TemplateCategory::whereName('Extended')->first();

        $stats = [
            'total' => SystemEmailTemplate::count(),
            'base' => $baseCategory ? SystemEmailTemplate::query()->categoryUid($baseCategory->uid)->count() : 0,
            'extended' => $extendedCategory ? SystemEmailTemplate::query()->categoryUid($extendedCategory->uid)->count() : 0,
        ];

        return view('campaign::manager.email-templates.index', compact('stats'));
    }

    public function listing(Request $request)
    {
        if (Gate::denies('read', new SystemEmailTemplate)) {
            $this->denied();
        }

        $query = SystemEmailTemplate::query()->search($request->keyword);

        if (! empty($request->category_uid)) {
            $query = $query->categoryUid($request->category_uid);
        }

        $tab = $request->tab;
        if (in_array($tab, ['base', 'extended'], true)) {
            $category = TemplateCategory::whereName($tab === 'base' ? 'Base' : 'Extended')->first();
            if ($category) {
                $query = $query->categoryUid($category->uid);
            }
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->view === 'grid' ? 16 : ($request->per_page ?? 15);
        $items = $query->paginate($perPage)->withQueryString();

        $view = $request->view === 'grid' ? '_grid' : '_list';

        return view('campaign::manager.email-templates.'.$view, compact('items'));
    }

    public function gallery(Request $request)
    {
        if (Gate::denies('create', new SystemEmailTemplate)) {
            $this->denied();
        }

        $tab = in_array($request->tab, ['base', 'extended'], true) ? $request->tab : 'extended';
        $category = TemplateCategory::whereName($tab === 'base' ? 'Base' : 'Extended')->first();

        $query = SystemEmailTemplate::query()->orderBy('created_at', 'desc');
        if (! empty($request->keyword)) {
            $query->search($request->keyword);
        }
        if ($category) {
            $query->categoryUid($category->uid);
        }

        $items = $query->paginate(16)->withQueryString();

        return view('campaign::manager.email-templates._gallery', compact('items', 'tab'));
    }

    public function preview(Request $request, string $uid)
    {
        $item = SystemEmailTemplate::findByUid($uid);

        if (! $item) {
            return redirect()->back();
        }

        if (Gate::denies('read', $item)) {
            $this->denied();
        }

        return $item->template->getPreviewContent();
    }

    // ── WRITE ──────────────────────────────────────────────────────────────────

    public function add(Request $request)
    {
        if (Gate::denies('create', new SystemEmailTemplate)) {
            $this->denied();
        }

        $template = SystemEmailTemplate::newDefault();
        $template->name = trans('campaign::email-templates.untitled');

        return view('campaign::manager.email-templates.create', compact('template'));
    }

    public function store(CreateEmailTemplateRequest $request): JsonResponse
    {
        $tpl = $this->service->createFromBase($request->toDto());

        if (! $tpl) {
            return response()->json([
                'status' => 'error',
                'errors' => ['template' => [trans('campaign::email-templates.errors.base_not_found')]],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::email-templates.flash.created'),
            'redirect' => route('manager.email_templates.builder', $tpl->uid),
        ]);
    }

    public function copy(Request $request, string $uid)
    {
        $item = SystemEmailTemplate::findByUid($uid);

        if (! $item) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        if (Gate::denies('read', $item)) {
            $this->denied();
        }

        return view('campaign::manager.email-templates.copy', compact('item'));
    }

    public function storeCopy(CopyEmailTemplateRequest $request, string $uid): JsonResponse
    {
        $newItem = $this->service->copyFrom($request->toDto($uid));

        if (! $newItem) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::email-templates.flash.copied'),
        ]);
    }

    public function changeName(Request $request, string $uid)
    {
        $item = SystemEmailTemplate::findByUid($uid);

        if (! $item) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        if (Gate::denies('read', $item)) {
            $this->denied();
        }

        return view('campaign::manager.email-templates.rename', compact('item'));
    }

    public function updateName(RenameEmailTemplateRequest $request, string $uid): JsonResponse
    {
        $ok = $this->service->rename($request->toDto($uid));

        if (! $ok) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::email-templates.flash.renamed'),
        ]);
    }

    public function delete(DeleteEmailTemplatesRequest $request): JsonResponse
    {
        $this->service->bulkDelete($request->toDto());

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::email-templates.flash.deleted'),
        ]);
    }

    public function builder(Request $request, string $uid)
    {
        $tpl = SystemEmailTemplate::findByUid($uid);

        if (! $tpl || Gate::denies('update', $tpl)) {
            $this->denied();
        }

        if ($request->isMethod('get') && $tpl->template?->isBuilderUploaded()) {
            return redirect()->route('manager.email_templates.custom_html', $tpl->uid);
        }

        if ($request->isMethod('post')) {
            $result = $this->service->saveContent(SaveContentDto::fromRequest($uid, $request->all()));

            if (! $result['ok']) {
                return response()->json($result['errors'], 400);
            }

            return response()->json(['status' => 'success']);
        }

        return view('campaign::manager.email-templates.builder', ['systemEmailTemplate' => $tpl]);
    }

    public function customHtml(Request $request, string $uid)
    {
        $tpl = SystemEmailTemplate::findByUid($uid);

        if (! $tpl || Gate::denies('update', $tpl)) {
            $this->denied();
        }

        if ($request->isMethod('post')) {
            $request->validate(['html' => 'required|string']);

            if ($tpl->template?->isBuilderUploaded()) {
                $validator = $tpl->template->updateBuilderContent(
                    Template::DEFAULT_BUILDER_JSON,
                    $request->html
                );

                if ($validator->fails()) {
                    if ($request->ajax()) {
                        return response()->json($validator->errors(), 400);
                    }

                    return back()->withErrors($validator)->withInput();
                }
            } else {
                TemplateService::for($tpl)->setCustomHtml($request->html, $tpl->name);
            }

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => trans('campaign::email-templates.flash.custom_html_saved'),
                    'redirect' => route('manager.email_templates.index'),
                ]);
            }

            return redirect()->route('manager.email_templates.index');
        }

        $currentHtml = $tpl->template?->isBuilderUploaded()
            ? ($tpl->template->content ?? '')
            : '';

        return view('campaign::manager.email-templates.custom_html', [
            'systemEmailTemplate' => $tpl,
            'currentHtml' => $currentHtml,
        ]);
    }
}
