<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Campaign\Dto\PageTemplate\SaveContentDto;
use Modules\Campaign\Http\Requests\PageTemplate\CopyPageTemplateRequest;
use Modules\Campaign\Http\Requests\PageTemplate\CreatePageTemplateRequest;
use Modules\Campaign\Http\Requests\PageTemplate\DeletePageTemplatesRequest;
use Modules\Campaign\Http\Requests\PageTemplate\RenamePageTemplateRequest;
use Modules\Campaign\Models\Template\PageTemplate;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Models\Template\TemplateCategory;
use Modules\Campaign\Services\PageTemplateService;
use Modules\Campaign\Services\TemplateService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * PageTemplateController — orquestación fina: FormRequest (auth + validación)
 * → DTO → Service → Response. Portado de acellemail
 * (App\Http\Controllers\Refactor\Admin\PageTemplateController), adaptado al
 * módulo: vistas campaign::manager.page-templates.*, autz vía Gate/Policy.
 */
class PageTemplateController extends Controller
{
    public function __construct(
        private readonly PageTemplateService $service,
    ) {}

    private function denied()
    {
        abort(Response::HTTP_FORBIDDEN);
    }

    // ── READ ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        if (Gate::denies('read', new PageTemplate)) {
            $this->denied();
        }

        $baseCategory = TemplateCategory::whereName('Base')->first();
        $extendedCategory = TemplateCategory::whereName('Extended')->first();

        $stats = [
            'total' => PageTemplate::count(),
            'base' => $baseCategory ? PageTemplate::query()->categoryUid($baseCategory->uid)->count() : 0,
            'extended' => $extendedCategory ? PageTemplate::query()->categoryUid($extendedCategory->uid)->count() : 0,
        ];

        return view('campaign::manager.page-templates.index', compact('stats'));
    }

    public function listing(Request $request)
    {
        if (Gate::denies('read', new PageTemplate)) {
            $this->denied();
        }

        $query = PageTemplate::query()->search($request->keyword);

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

        return view('campaign::manager.page-templates.'.$view, compact('items'));
    }

    public function gallery(Request $request)
    {
        if (Gate::denies('create', new PageTemplate)) {
            $this->denied();
        }

        $tab = in_array($request->tab, ['base', 'extended'], true) ? $request->tab : 'extended';
        $category = TemplateCategory::whereName($tab === 'base' ? 'Base' : 'Extended')->first();

        $query = PageTemplate::query()->orderBy('created_at', 'desc');
        if (! empty($request->keyword)) {
            $query->search($request->keyword);
        }
        if ($category) {
            $query->categoryUid($category->uid);
        }

        $items = $query->paginate(16)->withQueryString();

        return view('campaign::manager.page-templates._gallery', compact('items', 'tab'));
    }

    public function preview(Request $request, string $uid)
    {
        $item = PageTemplate::findByUid($uid);

        if (! $item) {
            return redirect()->back();
        }

        if (Gate::denies('read', $item)) {
            $this->denied();
        }

        return $item->template->getPreviewContent();
    }

    // ── WRITE (FormRequest → DTO → Service) ──────────────────────────────────

    public function add(Request $request)
    {
        if (Gate::denies('create', new PageTemplate)) {
            $this->denied();
        }

        $template = PageTemplate::newDefault();
        $template->name = trans('campaign::page-templates.untitled');

        return view('campaign::manager.page-templates.create', compact('template'));
    }

    public function store(CreatePageTemplateRequest $request): JsonResponse
    {
        $pageTemplate = $this->service->createFromBase($request->toDto());

        if (! $pageTemplate) {
            return response()->json([
                'status' => 'error',
                'errors' => ['template' => [trans('campaign::page-templates.errors.base_not_found')]],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::page-templates.flash.created'),
            'redirect' => route('manager.page_templates.builder', $pageTemplate->uid),
        ]);
    }

    public function copy(Request $request, string $uid)
    {
        $item = PageTemplate::findByUid($uid);

        if (! $item) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        if (Gate::denies('read', $item)) {
            $this->denied();
        }

        return view('campaign::manager.page-templates.copy', compact('item'));
    }

    public function storeCopy(CopyPageTemplateRequest $request, string $uid): JsonResponse
    {
        $newItem = $this->service->copyFrom($request->toDto($uid));

        if (! $newItem) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::page-templates.flash.copied'),
        ]);
    }

    public function changeName(Request $request, string $uid)
    {
        $item = PageTemplate::findByUid($uid);

        if (! $item) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        if (Gate::denies('read', $item)) {
            $this->denied();
        }

        return view('campaign::manager.page-templates.rename', compact('item'));
    }

    public function updateName(RenamePageTemplateRequest $request, string $uid): JsonResponse
    {
        $ok = $this->service->rename($request->toDto($uid));

        if (! $ok) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::page-templates.flash.renamed'),
        ]);
    }

    public function delete(DeletePageTemplatesRequest $request): JsonResponse
    {
        $this->service->bulkDelete($request->toDto());

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::page-templates.flash.deleted'),
        ]);
    }

    public function builder(Request $request, string $uid)
    {
        $pageTemplate = PageTemplate::findByUid($uid);

        if (! $pageTemplate || Gate::denies('update', $pageTemplate)) {
            $this->denied();
        }

        // Plantillas en custom HTML quedan fuera del builder (re-edición vía custom_html).
        if ($request->isMethod('get') && $pageTemplate->template?->isBuilderUploaded()) {
            return redirect()->route('manager.page_templates.custom_html', $pageTemplate->uid);
        }

        if ($request->isMethod('post')) {
            $result = $this->service->saveContent(SaveContentDto::fromRequest($uid, $request->all()));

            if (! $result['ok']) {
                return response()->json($result['errors'], 400);
            }

            return response()->json(['status' => 'success']);
        }

        return view('campaign::manager.page-templates.builder', compact('pageTemplate'));
    }

    public function customHtml(Request $request, string $uid)
    {
        $pageTemplate = PageTemplate::findByUid($uid);

        if (! $pageTemplate || Gate::denies('update', $pageTemplate)) {
            $this->denied();
        }

        if ($request->isMethod('post')) {
            $request->validate(['html' => 'required|string']);

            if ($pageTemplate->template?->isBuilderUploaded()) {
                $validator = $pageTemplate->template->updateBuilderContent(
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
                TemplateService::for($pageTemplate)
                    ->setCustomHtml($request->html, $pageTemplate->name);
            }

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => trans('campaign::page-templates.flash.custom_html_saved'),
                    'redirect' => route('manager.page_templates.index'),
                ]);
            }

            return redirect()->route('manager.page_templates.index');
        }

        $currentHtml = $pageTemplate->template?->isBuilderUploaded()
            ? ($pageTemplate->template->content ?? '')
            : '';

        return view('campaign::manager.page-templates.custom_html', [
            'pageTemplate' => $pageTemplate,
            'currentHtml' => $currentHtml,
        ]);
    }

    // ── Endpoints del builder (a donde POSTea builder.default) ───────────────

    /** Guarda json + content (builder.getData()/getHtml()). */
    public function builderEdit(Request $request, string $uid): JsonResponse
    {
        $template = Template::findByUid($uid);

        if (! $template) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $validator = $template->updateBuilderContent($request->json, $request->content);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        return response()->json(['status' => 'success']);
    }

    /** Aplica el contenido de otra plantilla (cambiar tema). */
    public function changeTemplate(Request $request, string $uid): JsonResponse
    {
        $template = Template::findByUid($uid);
        $fromTemplate = Template::findByUid($request->template_uid);

        if (! $template || ! $fromTemplate) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $template->json = $fromTemplate->json ?: Template::DEFAULT_BUILDER_JSON;
        $template->content = $fromTemplate->content;
        $template->theme = $fromTemplate->theme ?: 'default';
        $template->save();

        // Copiar assets del template origen.
        $srcDir = $fromTemplate->getStoragePath();
        if (File::isDirectory($srcDir)) {
            File::copyDirectory($srcDir, $template->getStoragePath());
        }

        return response()->json(['status' => 'success']);
    }

    /** Sirve assets del tema (resources/themes/{theme}/{path}). */
    public function themeAsset(Request $request, string $theme, ?string $path = null): BinaryFileResponse
    {
        $theme = preg_replace('/[^a-zA-Z0-9_\-]/', '', $theme) ?: 'default';
        $path = ltrim((string) $path, '/');

        // Evitar path traversal.
        if (str_contains($path, '..')) {
            abort(404);
        }

        $full = module_path('Campaign', 'resources/themes/'.$theme.'/'.$path);

        if ($path === '' || ! is_file($full)) {
            abort(404);
        }

        return response()->file($full);
    }

    /** Sirve el thumbnail almacenado de un Template (storage/app/templates/{uid}/thumbnail.*). */
    public function thumbnail(Request $request, string $uid)
    {
        $template = Template::findByUid($uid);

        if (! $template || ! $template->getThumbName()) {
            abort(404);
        }

        return response()->file($template->getStoragePath($template->getThumbName()));
    }

    /** Adapter de subida de imágenes para el builder → devuelve { url }. */
    public function assetUpload(Request $request): JsonResponse
    {
        $file = $request->file('file')
            ?? $request->file('image')
            ?? collect($request->allFiles())->flatten()->first();

        if (! $file) {
            return response()->json(['status' => 'error', 'message' => 'No file'], 422);
        }

        $name = Str::random(16).'.'.$file->getClientOriginalExtension();
        $stored = $file->storeAs('campaign/builder', $name, 'public');
        $url = Storage::disk('public')->url($stored);

        return response()->json(['status' => 'success', 'message' => 'Uploaded', 'url' => $url]);
    }

    /** Export HTML del builder (descarga). */
    public function exportHtml(Request $request): Response
    {
        $html = (string) $request->input('html', '');

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template.html"',
        ]);
    }

    /** Export ZIP (mínimo: html en un zip). */
    public function exportZip(Request $request): Response
    {
        $html = (string) $request->input('html', '');
        $tmp = tempnam(sys_get_temp_dir(), 'pt_zip_');
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('index.html', $html);
        $zip->close();

        return response()->download($tmp, 'template.zip')->deleteFileAfterSend(true);
    }

    /** Proxy RSS (stub — el widget RSS del builder lo consulta). */
    public function rssProxy(Request $request): JsonResponse
    {
        return response()->json(['items' => []]);
    }
}
