<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Models\Template\CustomerEmailTemplate;
use Modules\Campaign\Models\Template\SystemEmailTemplate;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Models\Template\TemplateCategory;
use Modules\Campaign\Services\TemplateService;
use Symfony\Component\HttpFoundation\Response;

/**
 * CustomerEmailTemplateController — plantillas de email de trabajo del usuario
 * ("Mis plantillas"). Portado de acellemail (Refactor\EmailTemplateController,
 * /email-templates). La galería de creación sale de SystemEmailTemplate. En
 * este destino no-SaaS las plantillas son globales (sin scope por customer).
 */
class CustomerEmailTemplateController extends Controller
{
    private function denied()
    {
        abort(Response::HTTP_FORBIDDEN);
    }

    public function index(Request $request)
    {
        if (Gate::denies('read', new CustomerEmailTemplate)) {
            $this->denied();
        }

        $stats = [
            'total' => CustomerEmailTemplate::count(),
        ];
        $categories = TemplateCategory::all();

        return view('campaign::manager.my-email-templates.index', compact('stats', 'categories'));
    }

    public function listing(Request $request)
    {
        if (Gate::denies('read', new CustomerEmailTemplate)) {
            $this->denied();
        }

        $query = CustomerEmailTemplate::query()->search($request->keyword);

        if ($request->category_uid) {
            $query = $query->categoryUid($request->category_uid);
        }

        $query->orderBy($request->sort_order ?: 'created_at', $request->sort_direction ?: 'desc');

        $perPage = $request->view === 'grid' ? 16 : ($request->per_page ?? 15);
        $items = $query->paginate($perPage)->withQueryString();

        $view = $request->view === 'grid' ? '_grid' : '_list';

        return view('campaign::manager.my-email-templates.'.$view, compact('items'));
    }

    /** Galería de SystemEmailTemplate (puntos de partida). */
    public function gallery(Request $request)
    {
        if (Gate::denies('create', new CustomerEmailTemplate)) {
            $this->denied();
        }

        $tab = in_array($request->tab, ['base', 'extended'], true) ? $request->tab : 'extended';
        $category = TemplateCategory::whereName($tab === 'base' ? 'Base' : 'Extended')->first();

        $query = SystemEmailTemplate::query()->orderBy('created_at', 'desc');
        if ($request->keyword) {
            $query->search($request->keyword);
        }
        if ($category) {
            $query->categoryUid($category->uid);
        }

        $items = $query->paginate(16)->withQueryString();

        return view('campaign::manager.my-email-templates._gallery', compact('items', 'tab'));
    }

    public function add(Request $request)
    {
        if (Gate::denies('create', new CustomerEmailTemplate)) {
            $this->denied();
        }

        $template = CustomerEmailTemplate::newDefault();
        $template->name = trans('campaign::email-templates.untitled');

        return view('campaign::manager.my-email-templates.create', compact('template'));
    }

    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('create', new CustomerEmailTemplate)) {
            $this->denied();
        }

        $request->validate(['name' => 'required|string|max:255', 'template' => 'required|string']);

        $systemTemplate = SystemEmailTemplate::findByUid($request->template);
        if (! $systemTemplate || ! $systemTemplate->template) {
            return response()->json([
                'status' => 'error',
                'errors' => ['template' => [trans('campaign::email-templates.errors.base_not_found')]],
            ], 422);
        }

        $new = new CustomerEmailTemplate;
        TemplateService::for($new)->setTemplate($systemTemplate->template, $request->name);

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::email-templates.flash.created'),
            'redirect' => route('manager.my_email_templates.builder', $new->uid),
        ]);
    }

    public function preview(Request $request, string $uid)
    {
        $item = CustomerEmailTemplate::findByUid($uid);

        if (! $item) {
            return redirect()->back();
        }

        if (Gate::denies('read', $item)) {
            $this->denied();
        }

        return response($item->template->getPreviewContent());
    }

    public function copy(Request $request, string $uid)
    {
        $item = CustomerEmailTemplate::findByUid($uid);

        if (! $item) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        if (Gate::denies('read', $item)) {
            $this->denied();
        }

        if ($request->isMethod('post')) {
            $request->validate(['name' => 'required|string|max:255']);

            $new = new CustomerEmailTemplate;
            TemplateService::for($new)->setTemplate($item->template, $request->name);

            return response()->json([
                'status' => 'success',
                'message' => trans('campaign::email-templates.flash.copied'),
            ]);
        }

        return view('campaign::manager.my-email-templates.copy', compact('item'));
    }

    public function rename(Request $request, string $uid)
    {
        $item = CustomerEmailTemplate::findByUid($uid);

        if (! $item) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        if (Gate::denies('update', $item)) {
            $this->denied();
        }

        if ($request->isMethod('post')) {
            $request->validate(['name' => 'required|string|max:255']);
            $item->name = $request->name;
            $item->save();

            return response()->json([
                'status' => 'success',
                'message' => trans('campaign::email-templates.flash.renamed'),
            ]);
        }

        return view('campaign::manager.my-email-templates.rename', compact('item'));
    }

    public function delete(Request $request): JsonResponse
    {
        if (Gate::denies('delete', new CustomerEmailTemplate)) {
            $this->denied();
        }

        $uids = is_array($request->uids) ? $request->uids : explode(',', (string) $request->uids);

        foreach ($uids as $uid) {
            $item = CustomerEmailTemplate::findByUid(trim($uid));
            if ($item && Gate::allows('delete', $item)) {
                $item->deleteAndCleanup();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::email-templates.flash.deleted'),
        ]);
    }

    public function builder(Request $request, string $uid)
    {
        $item = CustomerEmailTemplate::findByUid($uid);

        if (! $item) {
            return redirect()->route('manager.my_email_templates.index');
        }

        if (Gate::denies('update', $item)) {
            $this->denied();
        }

        if ($request->isMethod('get') && $item->template?->isBuilderUploaded()) {
            return redirect()->route('manager.my_email_templates.custom_html', $item->uid);
        }

        if ($request->isMethod('post')) {
            $validator = $item->template->updateBuilderContent($request->json, $request->content);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            return response()->json(['status' => 'success']);
        }

        return view('campaign::manager.my-email-templates.builder', ['customerEmailTemplate' => $item]);
    }

    public function customHtml(Request $request, string $uid)
    {
        $item = CustomerEmailTemplate::findByUid($uid);

        if (! $item || Gate::denies('update', $item)) {
            $this->denied();
        }

        if ($request->isMethod('post')) {
            $request->validate(['html' => 'required|string']);

            if ($item->template?->isBuilderUploaded()) {
                $validator = $item->template->updateBuilderContent(Template::DEFAULT_BUILDER_JSON, $request->html);
                if ($validator->fails()) {
                    return $request->ajax()
                        ? response()->json($validator->errors(), 400)
                        : back()->withErrors($validator)->withInput();
                }
            } else {
                TemplateService::for($item)->setCustomHtml($request->html, $item->name);
            }

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => trans('campaign::email-templates.flash.custom_html_saved'),
                    'redirect' => route('manager.my_email_templates.index'),
                ]);
            }

            return redirect()->route('manager.my_email_templates.index');
        }

        $currentHtml = $item->template?->isBuilderUploaded() ? ($item->template->content ?? '') : '';

        return view('campaign::manager.my-email-templates.custom_html', [
            'customerEmailTemplate' => $item,
            'currentHtml' => $currentHtml,
        ]);
    }

    public function changeTemplate(Request $request, string $uid): JsonResponse
    {
        $item = CustomerEmailTemplate::findByUid($uid);
        $fromTemplate = Template::findByUid($request->template_uid);

        if (! $item || ! $fromTemplate) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if (Gate::denies('update', $item)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $tpl = $item->template;
        $tpl->json = $fromTemplate->json ?: Template::DEFAULT_BUILDER_JSON;
        $tpl->content = $fromTemplate->content;
        $tpl->theme = $fromTemplate->theme ?: 'default';
        $tpl->save();

        $srcDir = $fromTemplate->getStoragePath();
        if (File::isDirectory($srcDir)) {
            File::copyDirectory($srcDir, $tpl->getStoragePath());
        }

        return response()->json(['status' => 'success']);
    }
}
