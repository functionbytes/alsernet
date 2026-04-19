<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Locales\Models\Locale;
use Modules\Page\Events\PageAutoSaved;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageVersion;
use Modules\Page\Services\PageAutoSaveService;
use Modules\Page\Services\PageService;
use Modules\Template\Models\Shortcode;
use Modules\Template\Services\TemplateManager;

class VisualEditorController extends Controller
{
    public function __construct(
        private readonly TemplateManager $templateManager,
        private readonly PageAutoSaveService $autoSaveService,
    ) {}

    /**
     * Render the visual editor shell for the given page.
     */
    public function index(Page $page): View
    {
        $this->authorize('update', $page);

        $page->load(['translations.localeModel', 'categories', 'tags']);

        $locale = app()->getLocale();
        $translation = $page->translations->firstWhere('locale', $locale)
            ?? $page->translations->first();

        $prefix = setting('permalink-modules-page-models-page', '');
        $slug = $translation?->slug ?? $page->slug;
        $publicUrl = url($prefix ? $prefix.'/'.$slug : $slug);

        $shortcodes = [];
        try {
            $shortcodes = app('shortcode')->getRegistered();
        } catch (\Throwable) {
        }

        if ($shortcodes) {
            $dbCategories = Shortcode::query()
                ->whereIn('key', array_column($shortcodes, 'name'))
                ->pluck('category', 'key')
                ->all();

            $shortcodes = array_map(function (array $sc) use ($dbCategories): array {
                if (isset($dbCategories[$sc['name']])) {
                    $sc['category'] = $dbCategories[$sc['name']];
                }

                return $sc;
            }, $shortcodes);
        }

        $initialContent = $translation?->content ?? $page->content ?? '';
        // Strip any auto-generated TOC and &nbsp; that may have leaked into saved content
        $initialContent = preg_replace('/<div[^>]*class="[^"]* [^"]*"[^>]*>.*?<\/div>\s*/is', '', $initialContent);
        $initialContent = str_replace(['&nbsp;', "\xC2\xA0"], ' ', $initialContent);

        $supportedLocales = PageService::getSupportedLocales();

        $draftInfo = $this->autoSaveService->getDraftInfo($page, auth()->user());

        return view('page::pages.visual-editor', [
            'page' => $page,
            'translation' => $translation,
            'locale' => $locale,
            'defaultLocale' => $supportedLocales[0] ?? $locale,
            'initialContent' => $initialContent,
            'previewUrl' => $publicUrl,
            'saveUrl' => route('pages.update', $page),
            'visualPreviewUrl' => route('pages.visual-preview', $page),
            'shortcodes' => $shortcodes,
            'supportedLocales' => $supportedLocales,
            'existingLocales' => $page->translations->pluck('locale')->all(),
            'draftInfo' => $draftInfo,
        ]);
    }

    /**
     * Render the page through the active theme for use inside the editor iframe.
     *
     * Accepts an optional `content` body parameter so the editor can push
     * unsaved HTML and see a live preview without touching the database.
     * Never tracks views or writes to cache.
     */
    public function preview(Request $request, Page $page): Response
    {
        $this->authorize('update', $page);

        $page->loadMissing('translations');

        $locale = $request->query('locale', app()->getLocale());
        $translation = $page->translations->firstWhere('locale', $locale)
            ?? $page->translations->first();
        $fallbackContent = $translation?->content ?? $page->content ?? '';
        $tempContent = $request->input('content', $fallbackContent);

        if (function_exists('shortcode')) {
            // Signal to shortcode callbacks that they should wrap their output with a
            // data-ve-sc sentinel div so the visual editor can recover the raw tag on save.
            app()->instance('ve_preview_wrap', true);
            $transContent = shortcode($tempContent);
            app()->forgetInstance('ve_preview_wrap');
        } else {
            $transContent = $tempContent;
        }

        $viewName = $this->resolveViewName($page);

        $data = [
            'page' => $page,
            'transTitle' => $translation?->seo_title ?? $translation?->title ?? $page->seo_title ?? $page->title,
            'transDescription' => $translation?->seo_description ?? $translation?->description ?? $page->seo_description ?? $page->description ?? '',
            'transKeywords' => $translation?->seo_keywords ?? $page->seo_keywords ?? '',
            'transContent' => $transContent,
            'canonicalUrl' => url()->current(),
            'xDefaultUrl' => null,
            'featuredImage' => $page->featured_image ?? null,
            'langLinks' => [],
            'detectedLocale' => app()->getLocale(),
            'isPreview' => true,
        ];

        $html = view($viewName, $data)->render();

        // Inject postMessage bridge so the CSS inspector can apply styles
        // to elements inside the preview iframe in real time.
        $bridge = $this->buildPostMessageBridge();
        $html = str_replace('</body>', $bridge.'</body>', $html);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Full save from the visual editor.
     *
     * Saves raw HTML content without running it through HTMLPurifier.
     * HTMLPurifier's allowlist is designed for user-submitted text and strips
     * custom elements, inline styles, data attributes, and IDs that the visual
     * editor legitimately produces. This endpoint is admin-only and trusts the
     * content coming from the visual editor canvas.
     */
    public function save(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);
        $startedAt = microtime(true);

        try {
            $response = $this->doSave($request, $page);
            $elapsedMs = (int) ((microtime(true) - $startedAt) * 1000);
            if ($elapsedMs > 500) {
                Log::info('visual_editor.save.slow', [
                    'page_id' => $page->id,
                    'user_id' => auth()->id(),
                    'elapsed_ms' => $elapsedMs,
                    'content_bytes' => strlen($request->input('content') ?? ''),
                ]);
            }

            return $response;
        } catch (\Throwable $e) {
            Log::error('visual_editor.save.failed', [
                'page_id' => $page->id,
                'user_id' => auth()->id(),
                'exception' => $e->getMessage(),
                'content_bytes' => strlen($request->input('content') ?? ''),
                'locale' => $request->input('locale'),
            ]);
            throw $e;
        }
    }

    private function doSave(Request $request, Page $page): JsonResponse
    {
        // Resolve locale_id up-front so we can scope the slug uniqueness rule
        // to (page_id ≠ current, locale_id = current) and avoid collisions
        // with other pages' slugs in the same locale.
        $locale = $request->input('locale');
        $localeModel = $locale ? Locale::where('code', $locale)->first() : null;

        $data = $request->validate([
            'locale' => ['required', 'string', 'max:10'],
            // 2 MB cap protects the DB and PHP memory from accidentally-huge
            // payloads (embedded data URIs, copy-paste of giant tables, etc.).
            'content' => ['nullable', 'string', 'max:2097152'],
            'template' => [
                'nullable',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! $value) {
                        return;
                    }
                    $tm = app(TemplateManager::class);
                    if (! $tm->hasTemplate($value)) {
                        $fail("El template '{$value}' no existe.");
                    }
                },
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('page_translations', 'slug')
                    ->where(fn ($q) => $q->where('locale_id', $localeModel?->id))
                    ->ignore($page->id, 'page_id'),
            ],
            'status' => ['nullable', 'string', 'max:50'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
        ]);

        $locale = $data['locale'];
        $content = $data['content'] ?? null;

        // Update page-level fields (template applies to all locales).
        $pageFields = array_filter(['template' => $data['template'] ?? null], fn ($v) => $v !== null);
        if (! empty($pageFields)) {
            $page->update($pageFields);
        }

        // Upsert translation — raw HTML, no sanitization.
        $translationFields = array_filter([
            'content' => $content,
            'title' => $data['title'] ?? null,
            'slug' => $data['slug'] ?? null,
            'status' => $data['status'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'seo_keywords' => $data['seo_keywords'] ?? null,
        ], fn ($v) => $v !== null);

        if (! empty($translationFields)) {
            // Resolve locale_id from locale code
            $localeModel = Locale::where('code', $locale)->first();
            if ($localeModel) {
                $page->translations()->updateOrCreate(
                    ['locale_id' => $localeModel->id],
                    $translationFields
                );
            }
        }

        // Snapshot the saved state as a new version.
        // We build it from request data + fresh page so it reflects what was just saved,
        // independent of whether `pages` table mirrors the active locale's translation.
        $page = $page->fresh();
        $nextVersionNumber = ((int) $page->versions()->max('version_number')) + 1;
        $version = $page->versions()->create([
            'version_number' => $nextVersionNumber,
            'title' => $data['title'] ?? $page->title,
            'content' => $content ?? $page->content,
            'description' => $page->description,
            'user_id' => auth()->id(),
            'template' => $data['template'] ?? $page->template,
            'status' => $data['status'] ?? (is_object($page->status) ? $page->status->value : $page->status),
            'slug' => $data['slug'] ?? $page->slug,
            'seo_title' => $data['seo_title'] ?? $page->seo_title,
            'seo_description' => $data['seo_description'] ?? $page->seo_description,
            'seo_keywords' => $data['seo_keywords'] ?? $page->seo_keywords,
        ]);

        Cache::forget($this->versionsCacheKey($page));
        $this->pruneOldVersions($page);

        return response()->json([
            'success' => true,
            'message' => 'Página guardada correctamente.',
            'version' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
            ],
        ]);
    }

    /**
     * Keep at most MAX_VERSIONS_PER_PAGE per page; delete older ones.
     * Prevents page_versions from growing unbounded on high-churn pages.
     */
    private const MAX_VERSIONS_PER_PAGE = 50;

    private function pruneOldVersions(Page $page): void
    {
        $count = $page->versions()->count();
        if ($count <= self::MAX_VERSIONS_PER_PAGE) {
            return;
        }

        $idsToDelete = $page->versions()
            ->orderByDesc('version_number')
            ->skip(self::MAX_VERSIONS_PER_PAGE)
            ->take($count - self::MAX_VERSIONS_PER_PAGE)
            ->pluck('id');

        if ($idsToDelete->isNotEmpty()) {
            PageVersion::whereIn('id', $idsToDelete)->delete();
        }
    }

    /**
     * Return the current auto-save draft content as JSON.
     */
    public function getDraft(Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $draft = $this->autoSaveService->getLatestDraft($page, auth()->user());

        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'Sin borrador activo.']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'content' => $draft->data['content'] ?? $draft->content ?? '',
                'saved_at' => $draft->saved_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Auto-save draft from the visual editor (web route, session auth).
     */
    public function autoSave(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $data = $request->validate([
            // Same 2 MB cap as save() to keep drafts proportional to saves.
            'content' => ['nullable', 'string', 'max:2097152'],
            'locale' => ['nullable', 'string', 'max:10'],
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
        ]);

        $draft = $this->autoSaveService->saveAutoSave($page, auth()->user(), $data);

        PageAutoSaved::dispatch($page, $draft);

        return response()->json([
            'success' => true,
            'message' => 'Borrador guardado automáticamente',
            'data' => [
                'saved_at' => $draft->saved_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Expand a shortcode tag to its rendered HTML (visual editor only).
     *
     * Processes ONLY the submitted shortcode string — not the full page content —
     * to avoid PCRE backtrack-limit failures on large content bodies.
     */
    public function expandShortcode(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $data = $request->validate(['shortcode' => ['required', 'string', 'max:10000']]);

        $html = function_exists('shortcode') ? shortcode($data['shortcode']) : $data['shortcode'];

        return response()->json(['html' => $html]);
    }

    /**
     * Return translation content for a given locale (JSON endpoint for locale switcher).
     */
    public function getLocaleContent(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $locale = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', PageService::getSupportedLocales())],
        ])['locale'];

        $page->loadMissing('translations');
        $translation = $page->translations->firstWhere('locale', $locale);

        return response()->json([
            'locale' => $locale,
            'content' => $translation?->content ?? '',
            'title' => $translation?->title ?? $page->title,
            'slug' => $translation?->slug ?? $page->slug,
            'status' => $translation?->status ?? $page->status->value,
            'seo_title' => $translation?->seo_title ?? $page->seo_title ?? '',
            'seo_description' => $translation?->seo_description ?? $page->seo_description ?? '',
            'seo_keywords' => $translation?->seo_keywords ?? $page->seo_keywords ?? '',
            'is_new' => $translation === null,
        ]);
    }

    /**
     * Return page versions list as JSON for the visual editor history panel.
     */
    public function getEditorVersions(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        // Cache the versions list per page for 5 minutes. Invalidated from
        // save() whenever a new version is created (see cache forget below).
        $data = Cache::remember(
            $this->versionsCacheKey($page),
            now()->addMinutes(5),
            function () use ($page) {
                return PageVersion::query()
                    ->where('page_id', $page->id)
                    ->with('user:id,name')
                    ->orderByDesc('version_number')
                    ->limit(20)
                    ->get(['id', 'version_number', 'title', 'user_id', 'created_at'])
                    ->map(fn (PageVersion $v) => [
                        'id' => $v->id,
                        'version_number' => $v->version_number,
                        'title' => $v->title,
                        'user' => $v->user?->name ?? '—',
                        'created_at' => $v->created_at->toIso8601String(),
                        'created_at_human' => $v->created_at->diffForHumans(),
                    ])
                    ->values()
                    ->all();
            }
        );

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function versionsCacheKey(Page $page): string
    {
        return "page:{$page->id}:versions:list:v1";
    }

    /**
     * Return a single version's content as JSON for the visual editor.
     */
    public function getEditorVersion(Request $request, Page $page, PageVersion $version): JsonResponse
    {
        $this->authorize('update', $page);

        if ($version->page_id !== $page->id) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'title' => $version->title,
                'content' => $version->content ?? '',
            ],
        ]);
    }

    /**
     * Build a <script> block injected into the preview iframe.
     *
     * Responsibilities:
     * - Select elements on click and notify the parent with computed styles.
     * - Apply style changes received from the parent inspector panel.
     * - Return the full body HTML on demand so the parent can save it,
     *   capturing both CKEditor text changes and inspector style changes.
     * - Re-apply persisted style changes after the preview refreshes.
     * - Support delete, move, and duplicate element operations.
     * - Expose a right-click context menu via postMessage.
     */
    private function buildPostMessageBridge(): string
    {
        return <<<'HTML'
<style>
.ve-sel      { outline: 2px solid #b10100 !important; outline-offset: 1px !important; }
.ve-ctx      { outline: 2px solid #FA896B !important; outline-offset: 1px !important; }
.ve-multi    { outline: 2px solid #fd7e14 !important; outline-offset: 1px !important; }
.ve-sc-active{ outline: 2px dashed #90bb13 !important; outline-offset: 4px !important; }
@keyframes veScFlash { 0%{outline-color:#90bb13;outline-width:2px} 40%{outline-color:#13C672;outline-width:4px} 100%{outline-color:#90bb13;outline-width:2px} }
.ve-sc-flash { animation: veScFlash .55s ease-out; }
.ve-quick-bar { position:absolute; display:inline-flex; gap:1px; padding:4px; background:#18181b; border:1px solid rgba(255,255,255,.08); border-radius:8px; box-shadow:0 12px 28px rgba(0,0,0,.25); z-index:99999; pointer-events:auto; font-family:'Inter',sans-serif; }
.ve-quick-bar button { width:28px; height:28px; display:flex; align-items:center; justify-content:center; background:transparent; border:none; border-radius:5px; color:#d4d4d8; font-size:11.5px; cursor:pointer; transition:background .1s,color .1s; }
.ve-quick-bar button:hover { background:rgba(255,255,255,.08); color:#fff; }
.ve-quick-bar button.ve-qb-danger:hover { background:rgba(239,68,68,.2); color:#f87171; }
.ve-quick-bar .qb-bold { font-weight:700; }
.ve-quick-bar .qb-italic { font-style:italic; }
.ve-quick-bar .qb-edit-block { color:#a3e635; font-size:11px; font-weight:700; }
.ve-quick-bar .ve-qb-sep { width:1px; background:rgba(255,255,255,.1); margin:4px 2px; display:inline-block; flex-shrink:0; }
.ve-sc-hover { outline: 2px dashed #FEC90F !important; outline-offset: 4px !important; background: rgba(254,201,15,0.05) !important; }
#ve-grid-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    pointer-events: none; z-index: 9998; display: none;
    background: repeating-linear-gradient(
        to right,
        rgba(144,187,19,0.08) 0px,
        rgba(144,187,19,0.08) calc(8.333% - 16px),
        transparent calc(8.333% - 16px),
        transparent 8.333%
    );
}
#ve-grid-overlay.active { display: block; }
</style>
<!-- Font Awesome (only loaded if not already present in host page) -->
<script>(function () {
    if (document.querySelector('link[href*="fontawesome"], script[src*="fontawesome"]')) return;
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
    link.crossOrigin = 'anonymous';
    link.referrerPolicy = 'no-referrer';
    document.head.appendChild(link);
})();</script>
<div id="ve-grid-overlay"></div>
<script>
(function () {
    var selectedEl    = null;
    var selectedId    = null;
    var idCounter     = 0;
    var isEditing     = false;
    var editingEl     = null;
    var multiSelected = [];
    var _veScCounter  = 0;

    // Canvas improvements: hover inspect
    var _veHoverInspectMode = false;
    var _veHoverOverlay     = null;

    function generateId() {
        return 've-el-' + Date.now() + '-' + (++idCounter);
    }

    function cleanForExport() {
        hideQuickBar();
        // Remove CSS selection classes (current approach)
        document.querySelectorAll('.ve-sel,.ve-ctx,.ve-multi,.ve-sc-active').forEach(function (el) {
            el.classList.remove('ve-sel', 've-ctx', 've-multi', 've-sc-active');
        });
        window._veActiveSentinel = null;
        // Strip inline outline styles — backwards compat with content saved before the class-based fix
        document.querySelectorAll('[style]').forEach(function (el) {
            if (el.style.outline) { el.style.outline = ''; }
            if (el.style.outlineOffset) { el.style.outlineOffset = ''; }
        });
    }

    function restoreSelection() {
        if (selectedEl) { selectedEl.classList.add('ve-sel'); }
        multiSelected.forEach(function (el) { el.classList.add('ve-multi'); });
    }

    function showQuickBar(el) {
        hideQuickBar();
        var rect = el.getBoundingClientRect();
        var bar = document.createElement('div');
        bar.className = 've-quick-bar';
        bar.id = 've-quick-bar';
        var tag = el.tagName.toLowerCase();
        var isText = ['h1','h2','h3','h4','h5','h6','p','span','a','li','td','th','label','strong','em','blockquote'].indexOf(tag) !== -1;
        var isLink = tag === 'a';

        // Detect sentinel ancestor
        var hasSentinel = false;
        var qbCur = el;
        while (qbCur && qbCur !== document.body) {
            if (qbCur.hasAttribute('data-ve-sc')) { hasSentinel = true; break; }
            qbCur = qbCur.parentElement;
        }

        var btns = '<button title="Mover arriba" data-action="move-up"><i class="fa-solid fa-arrow-up"></i></button>' +
            '<button title="Mover abajo" data-action="move-down"><i class="fa-solid fa-arrow-down"></i></button>' +
            '<button title="Duplicar" data-action="duplicate"><i class="fa-regular fa-copy"></i></button>' +
            '<span class="ve-qb-sep"></span>';

        if (isText) {
            btns += '<button title="Negrita" data-action="bold" class="qb-bold"><i class="fa-solid fa-bold"></i></button>' +
                '<button title="Cursiva" data-action="italic" class="qb-italic"><i class="fa-solid fa-italic"></i></button>' +
                '<button title="Color" data-action="color"><i class="fa-solid fa-palette"></i></button>' +
                '<span class="ve-qb-sep"></span>';
        }
        if (isLink) {
            btns += '<button title="Editar enlace" data-action="edit-link"><i class="fa-solid fa-link"></i></button>';
        }
        if (hasSentinel) {
            btns += '<button title="Editar bloque" data-action="edit-block" class="qb-edit-block"><i class="fa-solid fa-puzzle-piece"></i></button>';
        }
        btns += '<button title="Condiciones de visibilidad" data-action="conditions"><i class="fa-solid fa-filter"></i></button>' +
            '<button title="Inspeccionar" data-action="inspect"><i class="fa-solid fa-gear"></i></button>' +
            '<button class="ve-qb-danger" title="Eliminar" data-action="delete"><i class="fa-solid fa-xmark"></i></button>';
        bar.innerHTML = btns;
        var top = window.scrollY + rect.top - 38;
        if (top < 4) top = window.scrollY + rect.bottom + 4;
        bar.style.position = 'absolute';
        bar.style.top = top + 'px';
        bar.style.left = (rect.left + rect.width / 2 - 80) + 'px';
        document.body.appendChild(bar);
        bar.addEventListener('click', function(e) {
            var btn = e.target.closest('button');
            if (!btn) return;
            e.stopPropagation();
            var act = btn.getAttribute('data-action');
            if (act === 'move-up') window.parent.postMessage({type:'ve-move-element',direction:'up'},'*');
            else if (act === 'move-down') window.parent.postMessage({type:'ve-move-element',direction:'down'},'*');
            else if (act === 'duplicate') window.parent.postMessage({type:'ve-duplicate-element'},'*');
            else if (act === 'delete') window.parent.postMessage({type:'ve-delete-element'},'*');
            else if (act === 'inspect') window.parent.postMessage({type:'ve-request-inspect'},'*');
            else if (act === 'bold') {
                if (selectedEl) {
                    var fw = window.getComputedStyle(selectedEl).fontWeight;
                    selectedEl.style.fontWeight = (parseInt(fw) >= 700) ? '400' : '700';
                    window.parent.postMessage({type:'ve-inline-edit-committed', html: extractContent()},'*');
                }
            }
            else if (act === 'italic') {
                if (selectedEl) {
                    var fs = window.getComputedStyle(selectedEl).fontStyle;
                    selectedEl.style.fontStyle = (fs === 'italic') ? 'normal' : 'italic';
                    window.parent.postMessage({type:'ve-inline-edit-committed', html: extractContent()},'*');
                }
            }
            else if (act === 'color') {
                window.parent.postMessage({type:'ve-request-inspect'},'*');
            }
            else if (act === 'edit-block') window.parent.postMessage({type:'ve-open-sc-editor'},'*');
            else if (act === 'edit-link') {
                window.parent.postMessage({type:'ve-open-link-editor', nodeId: selectedEl ? selectedEl.id : ''},'*');
            }
            else if (act === 'conditions') {
                var cur = (selectedEl && selectedEl.getAttribute)
                    ? (selectedEl.getAttribute('data-condition') || '')
                    : '';
                var tagLabel = selectedEl && selectedEl.tagName ? selectedEl.tagName.toLowerCase() : 'elemento';
                if (selectedEl && selectedEl.className && typeof selectedEl.className === 'string') {
                    var firstClass = selectedEl.className.split(' ').filter(Boolean)[0];
                    if (firstClass) tagLabel += '.' + firstClass;
                }
                window.parent.postMessage({type:'ve-open-conditions', tag: tagLabel, current: cur},'*');
            }
        });
    }
    function hideQuickBar() {
        var b = document.getElementById('ve-quick-bar');
        if (b) b.remove();
    }

    function extractContent() {
        var ck = document.querySelector('.ck-content');
        if (!ck) return document.body.innerHTML;
        var toc = ck.querySelector('.page-toc');
        if (toc) toc.remove();
        // Clone to avoid mutating the live preview DOM
        var clone = ck.cloneNode(true);
        // Restore shortcode sentinel wrappers → original raw tag text so the saved
        // content keeps [shortcode][/shortcode] tags instead of expanded HTML.
        clone.querySelectorAll('[data-ve-sc]').forEach(function (el) {
            var tag = el.getAttribute('data-ve-sc');
            var text = document.createTextNode(tag);
            el.parentNode.replaceChild(text, el);
        });
        return clone.innerHTML;
    }

    function commitInlineEdit() {
        if (!editingEl) return;
        editingEl.contentEditable = 'false';
        editingEl.removeAttribute('contenteditable');
        document.getElementById('ve-inline-toolbar').style.display = 'none';
        isEditing = false;

        // If editing happened inside a shortcode sentinel, break it so
        // the edited HTML is saved as-is instead of restoring the raw tag.
        var sentinel = editingEl.closest('[data-ve-sc]');
        if (sentinel) sentinel.removeAttribute('data-ve-sc');

        editingEl = null;
        window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
    }

    // ── Helper: position an element near another ────────────────────────
    function positionNearElement(floater, anchor) {
        var rect = anchor.getBoundingClientRect();
        var fh   = floater.offsetHeight || 36;
        var fw   = floater.offsetWidth  || 200;
        var top  = rect.top > fh + 8 ? rect.top - fh - 6 : rect.bottom + 6;
        var left = Math.min(rect.left, window.innerWidth - fw - 8);
        floater.style.display = 'flex';
        floater.style.top  = top  + 'px';
        floater.style.left = Math.max(4, left) + 'px';
    }

    // ── Dummy link popover (link editing handled via postMessage) ────────
    var linkPopover = { contains: function () { return false; } };

    // ── Inject floating inline toolbar (Notion-style, monochrome) ───────
    var tbStyleTag = document.createElement('style');
    tbStyleTag.textContent = [
        '#ve-inline-toolbar { display:none; position:fixed; z-index:99999; background:#18181b; border:1px solid rgba(255,255,255,.08); border-radius:8px; padding:4px; align-items:center; gap:1px; box-shadow:0 12px 28px rgba(0,0,0,.25); font-family:"Inter",sans-serif; }',
        '#ve-inline-toolbar button { width:28px; height:28px; border-radius:5px; color:#d4d4d8; font-size:11.5px; display:grid; place-items:center; background:transparent; border:none; cursor:pointer; transition:background .1s, color .1s; padding:0; }',
        '#ve-inline-toolbar button:hover { background:rgba(255,255,255,.08); color:#fff; }',
        '#ve-inline-toolbar button.on { background:#fff; color:#18181b; }',
        '#ve-inline-toolbar .ve-tb-sep { width:1px; background:rgba(255,255,255,.1); margin:4px 2px; align-self:stretch; }',
        '#ve-inline-toolbar select { background:transparent; color:#d4d4d8; border:none; font-size:11px; padding:4px 6px; cursor:pointer; border-radius:5px; font-family:"Inter",sans-serif; }',
        '#ve-inline-toolbar select:hover { background:rgba(255,255,255,.06); color:#fff; }',
        '#ve-inline-toolbar select option { background:#18181b; color:#fff; }',
        '#ve-inline-toolbar input[type="color"] { width:24px; height:24px; padding:0; border:none; border-radius:5px; background:transparent; cursor:pointer; }',
        '#ve-inline-toolbar button.ve-tb-done { background:#fff; color:#18181b; font-weight:600; }',
        '#ve-inline-toolbar button.ve-tb-done:hover { background:#e4e4e7; color:#18181b; }',
    ].join('\n');
    document.head.appendChild(tbStyleTag);

    var toolbar = document.createElement('div');
    toolbar.id = 've-inline-toolbar';
    var tbSep = '<span class="ve-tb-sep"></span>';
    toolbar.innerHTML = [
        '<select id="ve-tb-tag" title="Cambiar tag">',
        '<option value="">Párrafo</option><option value="h1">H1</option><option value="h2">H2</option><option value="h3">H3</option><option value="h4">H4</option><option value="p">P</option><option value="blockquote">Quote</option>',
        '</select>',
        tbSep,
        '<button data-cmd="bold" title="Negrita"><i class="fa-solid fa-bold"></i></button>',
        '<button data-cmd="italic" title="Cursiva"><i class="fa-solid fa-italic"></i></button>',
        '<button data-cmd="underline" title="Subrayado"><i class="fa-solid fa-underline"></i></button>',
        '<button data-cmd="strikeThrough" title="Tachado"><i class="fa-solid fa-strikethrough"></i></button>',
        tbSep,
        '<button id="ve-tb-link" title="Enlace"><i class="fa-solid fa-link"></i></button>',
        '<button title="Color de texto" style="position:relative;overflow:hidden;"><i class="fa-solid fa-palette"></i><input type="color" id="ve-tb-color" value="#000000" style="position:absolute;inset:0;opacity:0;cursor:pointer;"></button>',
        '<select id="ve-tb-size" title="Tamaño" style="min-width:58px;">',
        '<option value="">Tamaño</option><option value="12px">12</option><option value="14px">14</option><option value="16px">16</option><option value="18px">18</option><option value="20px">20</option><option value="24px">24</option><option value="28px">28</option><option value="32px">32</option><option value="36px">36</option><option value="48px">48</option>',
        '</select>',
        tbSep,
        '<button data-cmd="justifyLeft" title="Izquierda"><i class="fa-solid fa-align-left"></i></button>',
        '<button data-cmd="justifyCenter" title="Centro"><i class="fa-solid fa-align-center"></i></button>',
        '<button data-cmd="justifyRight" title="Derecha"><i class="fa-solid fa-align-right"></i></button>',
        tbSep,
        '<button id="ve-tb-done" class="ve-tb-done" title="Guardar (Esc)"><i class="fa-solid fa-check"></i></button>',
    ].join('');
    document.body.appendChild(toolbar);

    // Prevent toolbar mousedown from stealing focus
    toolbar.addEventListener('mousedown', function (e) { e.preventDefault(); });

    // E2: Tag change
    document.getElementById('ve-tb-tag').addEventListener('change', function () {
        var newTag = this.value;
        if (!newTag || !editingEl) return;
        var newEl = document.createElement(newTag);
        newEl.innerHTML = editingEl.innerHTML;
        // Copy classes and id
        if (editingEl.className) newEl.className = editingEl.className;
        if (editingEl.id) newEl.id = editingEl.id;
        editingEl.parentNode.replaceChild(newEl, editingEl);
        editingEl = newEl;
        selectedEl = newEl;
        newEl.contentEditable = 'true';
        newEl.focus();
        this.value = '';
    });

    // E3: Font size change
    document.getElementById('ve-tb-size').addEventListener('change', function () {
        if (!editingEl || !this.value) return;
        editingEl.style.fontSize = this.value;
        editingEl.focus();
        this.value = '';
    });

    // E3: Color change
    document.getElementById('ve-tb-color').addEventListener('input', function () {
        if (!editingEl) return;
        document.execCommand('foreColor', false, this.value);
        editingEl.focus();
    });

    // ── Toolbar button actions ───────────────────────────────────────────
    toolbar.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        if (!btn) return;

        var cmd = btn.dataset.cmd;
        if (cmd) {
            document.execCommand(cmd);
            if (editingEl) editingEl.focus();
            return;
        }

        if (btn.id === 've-tb-link') {
            var linkEl = (editingEl && editingEl.tagName === 'A') ? editingEl
                       : (selectedEl && selectedEl.tagName === 'A') ? selectedEl : null;
            var href   = linkEl ? (linkEl.getAttribute('href') || '') : '';
            var target = linkEl ? (linkEl.getAttribute('target') || '') : '';
            window.parent.postMessage({ type: 've-open-link-editor', href: href, target: target }, '*');
            return;
        }

        if (btn.id === 've-tb-done') {
            commitInlineEdit();
        }
    });

    window.addEventListener('message', function (e) {
        var d = e.data;
        if (!d || !d.type) return;

        switch (d.type) {

            case 've-apply-link':
                var _linkEl = (editingEl && editingEl.tagName === 'A') ? editingEl
                            : (selectedEl && selectedEl.tagName === 'A') ? selectedEl : null;
                if (_linkEl) {
                    _linkEl.href = d.href || '';
                    if (d.target) { _linkEl.setAttribute('target', d.target); }
                    else { _linkEl.removeAttribute('target'); }
                }
                if (isEditing) {
                    commitInlineEdit();
                } else {
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;

            case 've-apply-style':
                if (d.prop) {
                    var applyTargets = (multiSelected.length > 0) ? multiSelected : (selectedEl ? [selectedEl] : []);
                    applyTargets.forEach(function (tel) {
                        tel.style[d.prop] = d.value || '';
                    });
                    if (selectedEl) {
                        window.parent.postMessage({
                            type:   've-style-synced',
                            nodeId: selectedId,
                            prop:   d.prop,
                            value:  d.value || '',
                        }, '*');
                    }
                }
                break;

            case 've-apply-custom-css':
                if (selectedEl && d.css) {
                    selectedEl.style.cssText += ';' + d.css;
                    window.parent.postMessage({
                        type:   've-custom-css-synced',
                        nodeId: selectedId,
                        css:    d.css,
                    }, '*');
                }
                break;

            case 've-deselect':
                cleanForExport();
                hideQuickBar();
                selectedEl = null;
                selectedId = null;
                break;

            case 've-request-html':
                cleanForExport();
                var html = extractContent();
                restoreSelection();
                window.parent.postMessage({ type: 've-html-response', html: html }, '*');
                break;

            case 've-reapply-styles':
                var changes = d.changes || {};
                for (var nodeId in changes) {
                    var el = document.getElementById(nodeId);
                    if (!el) continue;
                    var props = changes[nodeId];
                    for (var prop in props) {
                        if (prop === '__customCss') {
                            el.style.cssText += ';' + props[prop];
                        } else {
                            el.style[prop] = props[prop];
                        }
                    }
                }
                break;

            case 've-delete-element':
                var targetDel = (d.nodeId ? document.getElementById(d.nodeId) : null) || selectedEl;
                if (targetDel) {
                    var deletedId = targetDel.id || selectedId;
                    targetDel.classList.remove('ve-sel', 've-ctx', 've-multi');
                    targetDel.remove();
                    if (selectedEl === targetDel) { selectedEl = null; selectedId = null; }
                    window.parent.postMessage({ type: 've-element-deleted', nodeId: deletedId }, '*');
                    var content = extractContent();
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: content }, '*');
                }
                break;

            case 've-move-element':
                if (selectedEl && d.direction) {
                    var parent = selectedEl.parentNode;
                    if (d.direction === 'up') {
                        var prev = selectedEl.previousElementSibling;
                        if (prev) parent.insertBefore(selectedEl, prev);
                    } else if (d.direction === 'down') {
                        var next = selectedEl.nextElementSibling;
                        if (next) parent.insertBefore(next, selectedEl);
                    }
                }
                break;

            case 've-duplicate-element':
                if (selectedEl) {
                    var clone = selectedEl.cloneNode(true);
                    clone.id  = generateId();
                    clone.classList.remove('ve-sel', 've-ctx', 've-multi');
                    selectedEl.parentNode.insertBefore(clone, selectedEl.nextSibling);
                }
                break;

            case 've-request-element-html':
                var target = d.nodeId ? document.getElementById(d.nodeId) : null;
                if (target) {
                    window.parent.postMessage({
                        type:      've-element-html-response',
                        nodeId:    d.nodeId,
                        tag:       target.tagName.toLowerCase(),
                        outerHTML: target.outerHTML,
                    }, '*');
                }
                break;

            case 've-set-element-html':
                var setTarget = d.nodeId ? document.getElementById(d.nodeId) : null;
                if (setTarget && d.html !== undefined) {
                    var tmp = document.createElement('div');
                    tmp.innerHTML = d.html;
                    if (tmp.firstChild) {
                        setTarget.parentNode.replaceChild(tmp.firstChild, setTarget);
                    }
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;

            case 've-apply-link':
                var linkTarget = d.nodeId ? document.getElementById(d.nodeId) : null;
                if (linkTarget) {
                    linkTarget.href = d.href || '';
                    linkTarget.setAttribute('target', d.target || '');
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;

            case 've-drag-over': {
                var dropEl = document.elementFromPoint(d.x, d.y);
                if (dropEl && dropEl !== document.body && dropEl !== document.documentElement) {
                    if (!dropEl.id) dropEl.id = generateId();
                    window._veDropTargetId = dropEl.id;
                    // Show drop indicator line
                    var ind = document.getElementById('ve-iframe-drop-ind');
                    if (!ind) {
                        ind = document.createElement('div');
                        ind.id = 've-iframe-drop-ind';
                        ind.style.cssText = 'position:fixed;z-index:99990;pointer-events:none;height:3px;background:#1a1a1a;border-radius:2px;transition:top .05s,left .05s,width .05s;';
                        document.body.appendChild(ind);
                    }
                    var r = dropEl.getBoundingClientRect();
                    ind.style.cssText += ';display:block;top:' + (r.bottom - 2) + 'px;left:' + r.left + 'px;width:' + r.width + 'px;';
                }
                break;
            }

            case 've-insert-html': {
                // Insert a snippet/HTML fragment: after the selected element if any,
                // otherwise at the end of the body.
                if (!d.html) break;
                var wrapperIns = document.createElement('div');
                wrapperIns.innerHTML = d.html;
                var nodes = Array.from(wrapperIns.childNodes);
                var anchor = selectedEl && selectedEl !== document.body ? selectedEl : null;
                nodes.forEach(function (n) {
                    if (anchor && anchor.parentNode) {
                        anchor.parentNode.insertBefore(n, anchor.nextSibling);
                        anchor = n;
                    } else {
                        document.body.appendChild(n);
                    }
                });
                window.parent.postMessage({ type: 've-block-dropped', html: extractContent() }, '*');
                break;
            }

            case 've-drop-block': {
                var target = window._veDropTargetId ? document.getElementById(window._veDropTargetId) : null;
                if (!target) {
                    var dropEl2 = document.elementFromPoint(d.x, d.y);
                    target = (dropEl2 && dropEl2 !== document.body && dropEl2 !== document.documentElement)
                        ? dropEl2
                        : document.body.lastElementChild;
                }
                if (d.html) {
                    var wrapper = document.createElement('div');
                    wrapper.innerHTML = d.html;
                    var newNode = wrapper.firstElementChild || wrapper.firstChild;
                    if (newNode) {
                        if (target && target !== document.body && target.parentNode) {
                            target.parentNode.insertBefore(newNode, target.nextSibling);
                        } else {
                            document.body.appendChild(newNode);
                        }
                    }
                }
                var ind2 = document.getElementById('ve-iframe-drop-ind');
                if (ind2) ind2.style.display = 'none';
                window._veDropTargetId = null;
                window.parent.postMessage({ type: 've-block-dropped', html: extractContent() }, '*');
                break;
            }

            case 've-drag-cancel': {
                var ind3 = document.getElementById('ve-iframe-drop-ind');
                if (ind3) ind3.style.display = 'none';
                window._veDropTargetId = null;
                break;
            }

            case 've-copy-request': {
                var copyEl = d.nodeId ? document.getElementById(d.nodeId) : selectedEl;
                if (copyEl) {
                    var clone = copyEl.cloneNode(true);
                    clone.removeAttribute('id');
                    clone.classList.remove('ve-sel', 've-ctx', 've-multi');
                    window.parent.postMessage({ type: 've-copy-response', html: clone.outerHTML }, '*');
                }
                break;
            }

            case 've-paste-element': {
                if (!d.html) break;
                var pasteTarget = d.nodeId ? document.getElementById(d.nodeId) : selectedEl;
                var wrapper2 = document.createElement('div');
                wrapper2.innerHTML = d.html;
                var pasteNode = wrapper2.firstElementChild || wrapper2.firstChild;
                if (pasteNode) {
                    if (!pasteNode.id) pasteNode.id = generateId();
                    if (pasteTarget && pasteTarget.parentNode) {
                        pasteTarget.parentNode.insertBefore(pasteNode, pasteTarget.nextSibling);
                    } else {
                        document.body.appendChild(pasteNode);
                    }
                }
                window.parent.postMessage({ type: 've-paste-done', html: extractContent() }, '*');
                break;
            }

            case 've-toggle-class': {
                var classEl = d.nodeId ? document.getElementById(d.nodeId) : selectedEl;
                if (classEl && d.cls) {
                    if (d.add) {
                        d.cls.split(' ').forEach(function (c) { if (c) classEl.classList.add(c); });
                    } else {
                        d.cls.split(' ').forEach(function (c) { if (c) classEl.classList.remove(c); });
                    }
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;
            }

            case 've-select-by-id': {
                var byIdEl = d.nodeId ? document.getElementById(d.nodeId) : null;
                if (byIdEl) {
                    // Simulate a click to select it
                    if (selectedEl) {
                        selectedEl.classList.remove('ve-sel', 've-ctx', 've-multi');
                    }
                    selectedEl  = byIdEl;
                    selectedId  = d.nodeId;
                    selectedEl.classList.add('ve-sel');
                    showQuickBar(selectedEl);
                    byIdEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    var cs2 = window.getComputedStyle(byIdEl);
                    // Build path
                    var path = [];
                    var cur  = byIdEl;
                    while (cur && cur !== document.body && cur !== document.documentElement) {
                        var pTag = cur.tagName ? cur.tagName.toLowerCase() : '?';
                        path.unshift({ tag: pTag, nodeId: cur.id || null });
                        cur = cur.parentElement;
                    }
                    path.unshift({ tag: 'body', nodeId: null });
                    // Detect sentinel (self or ancestor) for shortcode editing
                    var sentinel2El = null;
                    var sc2Cur = byIdEl;
                    while (sc2Cur && sc2Cur !== document.body) {
                        if (sc2Cur.hasAttribute('data-ve-sc')) { sentinel2El = sc2Cur; break; }
                        sc2Cur = sc2Cur.parentElement;
                    }
                    if (sentinel2El && !sentinel2El.id) {
                        sentinel2El.id = '_veSc' + (++_veScCounter);
                    }
                    if (window._veActiveSentinel && window._veActiveSentinel !== sentinel2El) {
                        window._veActiveSentinel.classList.remove('ve-sc-active');
                    }
                    if (sentinel2El) {
                        sentinel2El.classList.add('ve-sc-active');
                        window._veActiveSentinel = sentinel2El;
                    } else {
                        if (window._veActiveSentinel) window._veActiveSentinel.classList.remove('ve-sc-active');
                        window._veActiveSentinel = null;
                    }
                    window.parent.postMessage({
                        type:          've-element-selected',
                        tag:           byIdEl.tagName.toLowerCase(),
                        nodeId:        d.nodeId,
                        isLink:        byIdEl.tagName.toLowerCase() === 'a',
                        href:          byIdEl.getAttribute('href') || '',
                        linkTarget:    byIdEl.getAttribute('target') || '',
                        elId:          byIdEl.id || '',
                        elClass:       byIdEl.className || '',
                        elSrc:         byIdEl.getAttribute('src') || '',
                        elAlt:         byIdEl.getAttribute('alt') || '',
                        elHref:        byIdEl.getAttribute('href') || '',
                        elPlaceholder:        byIdEl.getAttribute('data-form-field-placeholder') || byIdEl.getAttribute('placeholder') || '',
                        elFormFieldId:        byIdEl.getAttribute('data-form-field-id') || '',
                        elFormFieldLabel:     byIdEl.getAttribute('data-form-field-label') || '',
                        elFormFieldHelp:      byIdEl.getAttribute('data-form-field-help') || '',
                        elFormFieldRequired:  byIdEl.getAttribute('data-form-field-required') || '0',
                        elInputType:          byIdEl.getAttribute('type') || '',
                        elVeSc:               sentinel2El ? sentinel2El.getAttribute('data-ve-sc') : '',
                        elVeScNodeId:         sentinel2El ? sentinel2El.id : '',
                        styles: {
                            'font-size': cs2.fontSize, 'font-weight': cs2.fontWeight,
                            'font-family': cs2.fontFamily, 'color': cs2.color,
                            'text-align': cs2.textAlign, 'line-height': cs2.lineHeight,
                            'padding-top': cs2.paddingTop, 'padding-right': cs2.paddingRight,
                            'padding-bottom': cs2.paddingBottom, 'padding-left': cs2.paddingLeft,
                            'margin-top': cs2.marginTop, 'margin-right': cs2.marginRight,
                            'margin-bottom': cs2.marginBottom, 'margin-left': cs2.marginLeft,
                            'background-color': cs2.backgroundColor, 'opacity': cs2.opacity,
                            'border-width': cs2.borderWidth, 'border-style': cs2.borderStyle,
                            'border-color': cs2.borderColor, 'border-radius': cs2.borderRadius,
                            'width': cs2.width, 'height': cs2.height, 'display': cs2.display,
                        },
                    }, '*');
                    window.parent.postMessage({ type: 've-element-path', path: path }, '*');
                    // Send dimensions
                    var selRect2 = byIdEl.getBoundingClientRect();
                    window.parent.postMessage({ type: 've-element-dims', width: Math.round(selRect2.width), height: Math.round(selRect2.height), x: Math.round(selRect2.left), y: Math.round(selRect2.top) }, '*');
                }
                break;
            }

            case 've-apply-attribute': {
                var attrEl = d.nodeId ? document.getElementById(d.nodeId) : selectedEl;
                if (attrEl) {
                    if (d.attr === 'id') {
                        attrEl.id = d.value || '';
                        if (selectedEl === attrEl) { selectedId = d.value || ''; }
                    } else if (d.attr === 'class') {
                        attrEl.className = d.value || '';
                    } else if (d.value) {
                        attrEl.setAttribute(d.attr, d.value);
                    } else {
                        attrEl.removeAttribute(d.attr);
                    }
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;
            }

            case 've-remove-style': {
                var rsEl = (d.nodeId ? document.getElementById(d.nodeId) : null) || selectedEl;
                if (rsEl && d.props) {
                    d.props.forEach(function (p) { rsEl.style[p] = ''; });
                }
                break;
            }

            case 've-update-shortcode-sentinel': {
                var scEl = d.scNodeId ? document.getElementById(d.scNodeId) : null;
                if (scEl) {
                    scEl.setAttribute('data-ve-sc', d.encodedSc);
                    scEl.innerHTML = d.html;
                    scEl.classList.remove('ve-sc-flash');
                    void scEl.offsetWidth; // reflow to restart animation
                    scEl.classList.add('ve-sc-flash');
                    setTimeout(function () { scEl.classList.remove('ve-sc-flash'); }, 600);
                    window.parent.postMessage({ type: 've-shortcode-sentinel-updated', scNodeId: d.scNodeId }, '*');
                }
                break;
            }

            case 've-load-font': {
                if (d.fontName) {
                    var fontLinkId = 've-gfont-' + d.fontName.replace(/\s+/g, '-').toLowerCase();
                    if (!document.getElementById(fontLinkId)) {
                        var fl = document.createElement('link');
                        fl.id   = fontLinkId;
                        fl.rel  = 'stylesheet';
                        fl.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(d.fontName) + ':wght@300;400;600;700&display=swap';
                        document.head.appendChild(fl);
                    }
                }
                break;
            }

            case 've-set-zoom': {
                var zoomVal = d.zoom || 1;
                // Use zoom where supported (Chrome/Safari), fallback to transform: scale
                if (CSS && CSS.supports && CSS.supports('zoom', '1')) {
                    document.body.style.zoom = zoomVal;
                    document.body.style.transform = '';
                    document.body.style.transformOrigin = '';
                } else {
                    document.body.style.zoom = '';
                    document.body.style.transform = 'scale(' + zoomVal + ')';
                    document.body.style.transformOrigin = 'top left';
                    document.body.style.width = (100 / zoomVal) + '%';
                }
                window.parent.postMessage({ type: 've-zoom-applied', zoom: zoomVal }, '*');
                break;
            }

            case 've-set-zoom-fit': {
                // Reset internal zoom to 1 — actual fit scaling is handled by the parent on the iframe element
                document.body.style.zoom = '';
                document.body.style.transform = '';
                document.body.style.transformOrigin = '';
                document.body.style.width = '';
                window.parent.postMessage({ type: 've-zoom-applied', zoom: 1 }, '*');
                break;
            }

            case 've-wireframe-toggle': {
                var wfId = 've-wireframe-style';
                var existing = document.getElementById(wfId);
                if (d.enabled && !existing) {
                    var s = document.createElement('style');
                    s.id = wfId;
                    s.textContent = '.ve-wireframe-active, .ve-wireframe-active * {' +
                        'background-image: none !important;' +
                        'background-color: transparent !important;' +
                        'color: #374151 !important;' +
                        'text-shadow: none !important;' +
                        'box-shadow: none !important;' +
                        'outline: 1px dashed #9ca3af !important;' +
                        'outline-offset: -1px !important;' +
                    '}' +
                    '.ve-wireframe-active img, .ve-wireframe-active video, .ve-wireframe-active picture {' +
                        'filter: grayscale(1) opacity(.15) !important;' +
                        'background: repeating-linear-gradient(45deg,#e5e7eb,#e5e7eb 6px,#f3f4f6 6px,#f3f4f6 12px) !important;' +
                        'border: 1px solid #9ca3af !important;' +
                    '}' +
                    '.ve-wireframe-active svg { fill: #9ca3af !important; stroke: #9ca3af !important; }';
                    document.head.appendChild(s);
                    document.body.classList.add('ve-wireframe-active');
                } else if (!d.enabled && existing) {
                    existing.remove();
                    document.body.classList.remove('ve-wireframe-active');
                }
                break;
            }

            case 've-search-text': {
                // Highlight text matches in the page
                var existingHL = document.querySelectorAll('.ve-search-hl');
                existingHL.forEach(function(el) {
                    var parent = el.parentNode;
                    parent.replaceChild(document.createTextNode(el.textContent), el);
                    parent.normalize();
                });
                var query = (d.query || '').toLowerCase();
                if (!query || query.length < 2) { window.parent.postMessage({ type: 've-search-results', count: 0 }, '*'); break; }
                var walker = document.createTreeWalker(document.querySelector('.ck-content') || document.body, NodeFilter.SHOW_TEXT, null, false);
                var count = 0;
                var textNodes = [];
                while (walker.nextNode()) textNodes.push(walker.currentNode);
                textNodes.forEach(function(node) {
                    var text = node.textContent;
                    var idx = text.toLowerCase().indexOf(query);
                    if (idx === -1) return;
                    var span = document.createElement('span');
                    span.className = 've-search-hl';
                    span.style.cssText = 'background:#b10100;color:#fff;border-radius:2px;padding:0 1px;';
                    span.textContent = text.substring(idx, idx + query.length);
                    var after = document.createTextNode(text.substring(idx + query.length));
                    var before = document.createTextNode(text.substring(0, idx));
                    var parent = node.parentNode;
                    parent.insertBefore(before, node);
                    parent.insertBefore(span, node);
                    parent.insertBefore(after, node);
                    parent.removeChild(node);
                    count++;
                });
                window.parent.postMessage({ type: 've-search-results', count: count }, '*');
                if (count > 0) {
                    var first = document.querySelector('.ve-search-hl');
                    if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                break;
            }
            case 've-clear-search': {
                document.querySelectorAll('.ve-search-hl').forEach(function(el) {
                    var parent = el.parentNode;
                    parent.replaceChild(document.createTextNode(el.textContent), el);
                    parent.normalize();
                });
                break;
            }
            case 've-search-nav': {
                var hls = document.querySelectorAll('.ve-search-hl');
                if (hls.length === 0) break;
                var currentHL = document.querySelector('.ve-search-hl-active');
                var arr = Array.from(hls);
                var cidx = currentHL ? arr.indexOf(currentHL) : -1;
                if (currentHL) currentHL.classList.remove('ve-search-hl-active');
                var nidx = d.dir === 'next' ? (cidx + 1) % arr.length : (cidx - 1 + arr.length) % arr.length;
                arr[nidx].classList.add('ve-search-hl-active');
                arr[nidx].style.outline = '2px solid #b10100';
                arr[nidx].scrollIntoView({ behavior: 'smooth', block: 'center' });
                break;
            }
            case 've-get-selected-html': {
                if (selectedEl) {
                    window.parent.postMessage({ type: 've-selected-html', html: selectedEl.outerHTML }, '*');
                }
                break;
            }
            case 've-inject-css': {
                var liveStyle = document.getElementById('ve-live-css');
                if (!liveStyle) { liveStyle = document.createElement('style'); liveStyle.id = 've-live-css'; document.head.appendChild(liveStyle); }
                liveStyle.textContent = d.css || '';
                break;
            }
            case 've-replay-animations': {
                document.querySelectorAll('[data-ve-motion]').forEach(function(el) {
                    var anim = el.style.animation;
                    el.style.animation = 'none';
                    el.offsetHeight; // trigger reflow
                    el.style.animation = anim;
                });
                break;
            }
            case 've-set-img-src': {
                var img = d.nodeId ? document.getElementById(d.nodeId) : null;
                if (img && img.tagName.toLowerCase() === 'img' && d.src) {
                    img.src = d.src;
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;
            }
            case 've-set-attr': {
                if (selectedEl && d.attr) {
                    selectedEl.setAttribute(d.attr, d.value || '');
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;
            }
            case 've-remove-attr': {
                if (selectedEl && d.attr) {
                    selectedEl.removeAttribute(d.attr);
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;
            }

            case 've-apply-global-fonts': {
                var gfStyle = document.getElementById('ve-global-fonts-style');
                if (!gfStyle) { gfStyle = document.createElement('style'); gfStyle.id = 've-global-fonts-style'; document.head.appendChild(gfStyle); }
                var css = '';
                if (d.heading) css += 'h1,h2,h3,h4,h5,h6{font-family:' + d.heading + ' !important;}';
                if (d.body) css += 'body,p,li,td,th,span,a,div{font-family:' + d.body + ' !important;}';
                gfStyle.textContent = css;
                window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                break;
            }
            case 've-apply-global-colors': {
                var gcStyle = document.getElementById('ve-global-colors-style');
                if (!gcStyle) { gcStyle = document.createElement('style'); gcStyle.id = 've-global-colors-style'; document.head.appendChild(gcStyle); }
                gcStyle.textContent = ':root{--color-primary:' + d.primary + ';--color-secondary:' + d.secondary + ';--color-accent:' + d.accent + ';}';
                break;
            }

            case 've-apply-motion': {
                if (selectedEl) {
                    // Inject animation CSS if not already
                    if (!document.getElementById('ve-motion-css')) {
                        var style = document.createElement('style');
                        style.id = 've-motion-css';
                        style.textContent = `
                            @keyframes veFadeIn { from{opacity:0} to{opacity:1} }
                            @keyframes veFadeInUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
                            @keyframes veFadeInDown { from{opacity:0;transform:translateY(-30px)} to{opacity:1;transform:translateY(0)} }
                            @keyframes veFadeInLeft { from{opacity:0;transform:translateX(-30px)} to{opacity:1;transform:translateX(0)} }
                            @keyframes veFadeInRight { from{opacity:0;transform:translateX(30px)} to{opacity:1;transform:translateX(0)} }
                            @keyframes veZoomIn { from{opacity:0;transform:scale(.8)} to{opacity:1;transform:scale(1)} }
                            @keyframes veSlideUp { from{transform:translateY(50px)} to{transform:translateY(0)} }
                            @keyframes veSlideDown { from{transform:translateY(-50px)} to{transform:translateY(0)} }
                        `;
                        document.head.appendChild(style);
                    }
                    var map = {'fade-in':'veFadeIn','fade-in-up':'veFadeInUp','fade-in-down':'veFadeInDown','fade-in-left':'veFadeInLeft','fade-in-right':'veFadeInRight','zoom-in':'veZoomIn','slide-up':'veSlideUp','slide-down':'veSlideDown'};
                    var anim = map[d.effect] || '';
                    if (anim) {
                        selectedEl.setAttribute('data-ve-motion', d.effect);
                        selectedEl.style.animation = anim + ' ' + (d.duration||'0.6s') + ' ' + (d.delay||'0s') + ' ease both';
                    }
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;
            }
            case 've-remove-motion': {
                if (selectedEl) {
                    selectedEl.removeAttribute('data-ve-motion');
                    selectedEl.style.animation = '';
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;
            }

            case 've-copy-styles': {
                if (selectedEl) {
                    var cs = window.getComputedStyle(selectedEl);
                    var styles = {};
                    var props = ['color','background-color','background-image','font-size','font-weight','font-family',
                        'line-height','letter-spacing','text-align','text-decoration','text-transform',
                        'padding','margin','border','border-radius','box-shadow','opacity',
                        'display','position','width','height','max-width','min-height'];
                    props.forEach(function(p){ styles[p] = cs.getPropertyValue(p); });
                    window.parent.postMessage({ type: 've-styles-copied', styles: styles }, '*');
                }
                break;
            }
            case 've-paste-styles': {
                if (selectedEl && d.styles) {
                    Object.keys(d.styles).forEach(function(p){
                        selectedEl.style.setProperty(p, d.styles[p]);
                    });
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
                }
                break;
            }

            case 've-toggle-grid': {
                var gridStyleId = 've-grid-overlay-style';
                var existing = document.getElementById(gridStyleId);
                if (existing) {
                    existing.remove();
                } else {
                    var gridStyle = document.createElement('style');
                    gridStyle.id = gridStyleId;
                    gridStyle.textContent = '* { outline: 1px solid rgba(144,187,19,0.2) !important; }';
                    document.head.appendChild(gridStyle);
                }
                break;
            }

            case 've-set-full-html': {
                var ck = document.querySelector('.ck-content');
                if (ck && d.html !== undefined) {
                    ck.innerHTML = d.html;
                    window.parent.postMessage({ type: 've-inline-edit-committed', html: d.html }, '*');
                }
                break;
            }

            case 've-scan-navigator': {
                // Full DOM tree scan for Navigator panel
                function scanNode(el, depth) {
                    if (depth > 6) return null;
                    if (!el || el.nodeType !== 1) return null;
                    var tag = el.tagName.toLowerCase();
                    if (['script','style','link','meta','noscript','svg','path'].indexOf(tag) !== -1) return null;
                    if (el.classList.contains('ve-quick-bar') || el.classList.contains('ve-sel')) { /* skip helpers */ }
                    if (!el.id) el.id = generateId();
                    var kids = [];
                    Array.from(el.children).forEach(function(child) {
                        var c = scanNode(child, depth + 1);
                        if (c) kids.push(c);
                    });
                    var label = tag;
                    if (el.id && !el.id.startsWith('ve-el-')) label += '#' + el.id;
                    else if (el.className) {
                        var cls = (typeof el.className === 'string' ? el.className : '').replace(/ve-sel|ve-ctx|ve-multi|ve-quick-bar/g,'').trim().split(/\s+/).slice(0,2).join('.');
                        if (cls) label += '.' + cls;
                    }
                    var text = '';
                    if (kids.length === 0) {
                        text = (el.textContent || '').trim().substring(0, 30);
                    }
                    return { nodeId: el.id, tag: tag, label: label, text: text, children: kids };
                }
                var ck = document.querySelector('.ck-content') || document.body;
                var tree = [];
                Array.from(ck.children).forEach(function(child) {
                    var n = scanNode(child, 0);
                    if (n) tree.push(n);
                });
                window.parent.postMessage({ type: 've-navigator-scan', data: tree }, '*');
                break;
            }

            case 've-scan-layout': {
                // Scan Bootstrap grid structure
                var containers = [];
                document.querySelectorAll('.container, .container-fluid, .container-xl, .container-lg, .container-md, .container-sm').forEach(function (cont) {
                    if (!cont.id) cont.id = generateId();
                    var rowsData = [];
                    cont.querySelectorAll(':scope > .row, :scope > * > .row').forEach(function (row) {
                        if (!row.id) row.id = generateId();
                        var colsData = [];
                        Array.from(row.children).forEach(function (col) {
                            if (!col.id) col.id = generateId();
                            // Detect col span
                            var spanMatch = (col.className || '').match(/col(?:-\w+)?-(\d+)/);
                            colsData.push({ nodeId: col.id, span: spanMatch ? parseInt(spanMatch[1]) : null, className: col.className });
                        });
                        rowsData.push({ nodeId: row.id, cols: colsData });
                    });
                    containers.push({
                        nodeId: cont.id,
                        tag:    cont.tagName.toLowerCase() + (cont.className ? '.' + cont.className.split(' ').join('.') : ''),
                        rows:   rowsData,
                    });
                });
                window.parent.postMessage({ type: 've-layout-scan', data: containers }, '*');
                break;
            }

            case 've-duplicate-sentinel': {
                var origEl = window._veNodeMap && window._veNodeMap[d.scNodeId];
                if (!origEl) break;
                var clone = origEl.cloneNode(true);
                var newId = 'VE-' + Math.random().toString(36).substr(2,8).toUpperCase();
                clone.setAttribute('data-ve-node-id', newId);
                origEl.parentNode.insertBefore(clone, origEl.nextSibling);
                if (window._veNodeMap) window._veNodeMap[newId] = clone;
                window.parent.postMessage({ type: 've-html-updated', html: document.body.innerHTML }, '*');
                break;
            }

            case 've-move-sentinel': {
                var el = window._veNodeMap && window._veNodeMap[d.scNodeId];
                if (!el || !el.parentNode) break;
                if (d.direction === 'up') {
                    var prev = el.previousElementSibling;
                    if (prev) el.parentNode.insertBefore(el, prev);
                } else {
                    var next = el.nextElementSibling;
                    if (next) el.parentNode.insertBefore(next, el);
                }
                window.parent.postMessage({ type: 've-html-updated', html: document.body.innerHTML }, '*');
                break;
            }

            case 've-highlight-sentinel-hover': {
                var el = window._veNodeMap && window._veNodeMap[d.scNodeId];
                if (!el) break;
                if (window._veHoverSentinel && window._veHoverSentinel !== el) {
                    window._veHoverSentinel.classList.remove('ve-sc-hover');
                }
                el.classList.add('ve-sc-hover');
                window._veHoverSentinel = el;
                break;
            }

            case 've-unhighlight-sentinel-hover': {
                if (window._veHoverSentinel) {
                    window._veHoverSentinel.classList.remove('ve-sc-hover');
                    window._veHoverSentinel = null;
                }
                break;
            }

            case 've-get-computed-styles': {
                var el = window._veNodeMap && window._veNodeMap[d.nodeId];
                if (!el) break;
                var cs = window.getComputedStyle(el);
                var props = ['color','background-color','font-size','font-weight','font-family','margin','padding','border-radius','border','text-align','line-height','letter-spacing','opacity','display','flex-direction','justify-content','align-items'];
                var styles = {};
                props.forEach(function(p) { styles[p] = cs.getPropertyValue(p); });
                window.parent.postMessage({ type: 've-computed-styles-result', styles: styles }, '*');
                break;
            }

            case 've-apply-styles': {
                var el = window._veNodeMap && window._veNodeMap[d.nodeId];
                if (!el || !d.styles) break;
                Object.keys(d.styles).forEach(function(prop) {
                    el.style.setProperty(prop, d.styles[prop]);
                });
                window.parent.postMessage({ type: 've-html-updated', html: document.body.innerHTML }, '*');
                break;
            }

            case 've-find-in-page': {
                document.querySelectorAll('.ve-find-highlight').forEach(function(el) {
                    el.outerHTML = el.innerHTML;
                });
                window._veFindMatches = [];
                window._veFindIdx = 0;
                if (!d.query) { window.parent.postMessage({type:'ve-find-results',count:0,current:0},'*'); break; }
                var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
                var nodes = [];
                while (walker.nextNode()) {
                    if (walker.currentNode.textContent.toLowerCase().includes(d.query.toLowerCase())) {
                        nodes.push(walker.currentNode);
                    }
                }
                nodes.forEach(function(node) {
                    var escaped = d.query.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
                    var html = node.textContent.replace(new RegExp(escaped, 'gi'), '<mark class="ve-find-highlight" style="background:#FEC90F;color:#000;">$&</mark>');
                    var span = document.createElement('span');
                    span.innerHTML = html;
                    node.parentNode.replaceChild(span, node);
                    span.querySelectorAll('.ve-find-highlight').forEach(function(m) { window._veFindMatches.push(m); });
                });
                var count = window._veFindMatches.length;
                if (count > 0) { window._veFindMatches[0].scrollIntoView({behavior:'smooth',block:'center'}); }
                window.parent.postMessage({type:'ve-find-results',count:count,current:count>0?1:0},'*');
                break;
            }

            case 've-find-navigate': {
                if (!window._veFindMatches || !window._veFindMatches.length) break;
                var n = window._veFindMatches.length;
                window._veFindIdx = ((window._veFindIdx + (d.dir === 'next' ? 1 : -1)) % n + n) % n;
                window._veFindMatches[window._veFindIdx].scrollIntoView({behavior:'smooth',block:'center'});
                window.parent.postMessage({type:'ve-find-results',count:n,current:window._veFindIdx+1},'*');
                break;
            }

            // ── Canvas improvements ──────────────────────────────────────────

            case 've-toggle-grid-overlay': {
                var go = document.getElementById('ve-grid-overlay');
                if (go) go.classList.toggle('active');
                break;
            }

            case 've-toggle-hover-inspect': {
                _veHoverInspectMode = !_veHoverInspectMode;
                if (!_veHoverInspectMode && _veHoverOverlay) {
                    _veHoverOverlay.style.display = 'none';
                }
                break;
            }
        }
    });

    // ── Hover inspector ──────────────────────────────────────────────────
    document.addEventListener('mousemove', function(e) {
        if (!_veHoverInspectMode) return;
        var el = document.elementFromPoint(e.clientX, e.clientY);
        if (!el || el.id === 've-hover-overlay' || (el.closest && el.closest('#ve-hover-overlay'))) return;

        if (!_veHoverOverlay) {
            _veHoverOverlay = document.createElement('div');
            _veHoverOverlay.id = 've-hover-overlay';
            _veHoverOverlay.style.cssText = 'position:fixed;pointer-events:none;z-index:9997;border:1px dashed #90bb13;background:rgba(144,187,19,0.05);transition:all .1s;';
            var hoverLabel = document.createElement('div');
            hoverLabel.id = 've-hover-label';
            hoverLabel.style.cssText = 'position:absolute;top:-20px;left:0;background:#90bb13;color:#fff;padding:1px 5px;font-size:10px;white-space:nowrap;border-radius:2px;font-family:monospace;';
            _veHoverOverlay.appendChild(hoverLabel);
            document.body.appendChild(_veHoverOverlay);
        }

        var rect = el.getBoundingClientRect();
        _veHoverOverlay.style.display = 'block';
        _veHoverOverlay.style.left    = rect.left + 'px';
        _veHoverOverlay.style.top     = rect.top + 'px';
        _veHoverOverlay.style.width   = rect.width + 'px';
        _veHoverOverlay.style.height  = rect.height + 'px';
        var label = document.getElementById('ve-hover-label');
        if (label) label.textContent = el.tagName.toLowerCase() + ' ' + Math.round(rect.width) + '×' + Math.round(rect.height) + 'px';
    });

    // ── Drag reorder ANY element within preview ──────────────────────────
    var dragEl = null, dragPlaceholder = null, dragStartY = 0, dragStartX = 0, isDragging = false, dragOrigParent = null, dragOrigNext = null;

    document.addEventListener('mousedown', function(e) {
        if (isEditing || !selectedEl || e.button !== 0) return;
        // Allow drag on any selected element (not just top-level)
        dragEl = selectedEl;
        dragStartY = e.clientY;
        dragStartX = e.clientX;
        isDragging = false;
        dragOrigParent = dragEl.parentNode;
        dragOrigNext = dragEl.nextSibling;
    });

    document.addEventListener('mousemove', function(e) {
        if (!dragEl) return;
        var dy = Math.abs(e.clientY - dragStartY);
        var dx = Math.abs(e.clientX - dragStartX);
        if (dy < 8 && dx < 8 && !isDragging) return;

        if (!isDragging) {
            isDragging = true;
            hideQuickBar();
            dragEl.style.opacity = '0.3';
            dragEl.style.outline = '2px dashed #b10100';
            dragPlaceholder = document.createElement('div');
            dragPlaceholder.className = 've-drag-placeholder';
            dragPlaceholder.style.cssText = 'height:4px;background:#b10100;border-radius:2px;margin:4px 0;pointer-events:none;';
        }

        // Find the element under cursor (excluding drag element and placeholder)
        dragEl.style.pointerEvents = 'none';
        dragPlaceholder.style.display = 'none';
        var target = document.elementFromPoint(e.clientX, e.clientY);
        dragEl.style.pointerEvents = '';
        dragPlaceholder.style.display = '';

        if (!target || target === dragEl || target === dragPlaceholder) return;

        // Don't drop inside itself
        if (dragEl.contains(target)) return;

        // Find the closest container-like element (div, section, td, li, col)
        var dropParent = target;
        // If target is a text node or small inline, go up to its block parent
        while (dropParent && dropParent.nodeType === 1) {
            var display = window.getComputedStyle(dropParent).display;
            if (display === 'block' || display === 'flex' || display === 'grid' || display === 'table-cell') break;
            dropParent = dropParent.parentElement;
        }
        if (!dropParent || dropParent === dragEl || dragEl.contains(dropParent)) return;

        // Insert placeholder relative to target within its parent
        var rect = target.getBoundingClientRect();
        var midY = rect.top + rect.height / 2;
        var parent = target.parentNode;
        if (!parent) return;

        if (e.clientY < midY) {
            parent.insertBefore(dragPlaceholder, target);
        } else {
            parent.insertBefore(dragPlaceholder, target.nextSibling);
        }
    });

    document.addEventListener('mouseup', function(e) {
        if (!dragEl) return;
        if (isDragging && dragPlaceholder && dragPlaceholder.parentNode) {
            dragPlaceholder.parentNode.insertBefore(dragEl, dragPlaceholder);
            dragPlaceholder.remove();
            dragEl.style.opacity = '';
            dragEl.style.outline = '';
            showQuickBar(dragEl);
            window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
            window.parent.postMessage({ type: 've-section-reordered' }, '*');
        } else if (dragEl) {
            dragEl.style.opacity = '';
            dragEl.style.outline = '';
        }
        if (dragPlaceholder && dragPlaceholder.parentNode) dragPlaceholder.remove();
        dragEl = null;
        dragPlaceholder = null;
        isDragging = false;
    });

    // ── Click to select element ──────────────────────────────────────────
    document.addEventListener('click', function (e) {
        if (isEditing) return;

        var el = e.target;
        if (!el || el === document.body || el === document.documentElement) return;
        e.preventDefault();
        e.stopPropagation();

        if (!el.id || el.id === '') el.id = generateId();

        // ── Single click on <i> icon → open Icon Picker directly ──
        var _tag = el.tagName.toLowerCase();
        if (_tag === 'i' || _tag === 'svg') {
            var _cls = (typeof el.className === 'string' ? el.className : (el.getAttribute('class') || ''));
            if (/(^|\s)(fa-|fas|far|fab|fal|fad|fa-solid|fa-regular|fa-brands|material-icons|icon-|bi-)/i.test(_cls)) {
                selectedEl = el;
                selectedId = el.id;
                window.parent.postMessage({
                    type: 've-open-icon-picker',
                    nodeId: el.id,
                    currentClass: _cls
                }, '*');
                return; // skip normal selection + quick-bar
            }
        }

        // Shift+click: multi-select
        if (e.shiftKey) {
            var alreadyIdx = multiSelected.indexOf(el);
            if (alreadyIdx === -1) {
                multiSelected.push(el);
                el.classList.add('ve-multi');
            } else {
                multiSelected.splice(alreadyIdx, 1);
                el.classList.remove('ve-multi');
            }
            window.parent.postMessage({ type: 've-multi-select', count: multiSelected.length }, '*');
            return;
        }

        // Clear multi-selection outlines
        multiSelected.forEach(function (mel) { mel.classList.remove('ve-multi'); });
        multiSelected = [];

        if (selectedEl) { selectedEl.classList.remove('ve-sel', 've-ctx', 've-multi'); }

        selectedEl = el;
        selectedId = el.id;
        selectedEl.classList.add('ve-sel');
        showQuickBar(el);

        var cs = window.getComputedStyle(el);

        // Detect sentinel (self or ancestor) for shortcode editing
        var sentinelEl = null;
        var scCur = el;
        while (scCur && scCur !== document.body) {
            if (scCur.hasAttribute('data-ve-sc')) { sentinelEl = scCur; break; }
            scCur = scCur.parentElement;
        }
        if (sentinelEl && !sentinelEl.id) {
            sentinelEl.id = '_veSc' + (++_veScCounter);
        }

        // Highlight active sentinel
        if (window._veActiveSentinel && window._veActiveSentinel !== sentinelEl) {
            window._veActiveSentinel.classList.remove('ve-sc-active');
        }
        if (sentinelEl) {
            sentinelEl.classList.add('ve-sc-active');
            window._veActiveSentinel = sentinelEl;
        } else {
            if (window._veActiveSentinel) window._veActiveSentinel.classList.remove('ve-sc-active');
            window._veActiveSentinel = null;
        }

        // Build DOM breadcrumb path
        var elPath = [];
        var pathCur = el;
        while (pathCur && pathCur !== document.body && pathCur !== document.documentElement) {
            elPath.unshift({ tag: pathCur.tagName.toLowerCase(), nodeId: pathCur.id || null });
            pathCur = pathCur.parentElement;
        }
        elPath.unshift({ tag: 'body', nodeId: null });

        window.parent.postMessage({
            type:          've-element-selected',
            tag:           el.tagName.toLowerCase(),
            nodeId:        selectedId,
            isLink:        el.tagName.toLowerCase() === 'a',
            href:          el.getAttribute('href') || '',
            linkTarget:    el.getAttribute('target') || '',
            elId:          el.id || '',
            elClass:       el.className || '',
            elSrc:         el.getAttribute('src') || '',
            elAlt:         el.getAttribute('alt') || '',
            elHref:        el.getAttribute('href') || '',
            elPlaceholder:        el.getAttribute('data-form-field-placeholder') || el.getAttribute('placeholder') || '',
            elFormFieldId:        el.getAttribute('data-form-field-id') || '',
            elFormFieldLabel:     el.getAttribute('data-form-field-label') || '',
            elFormFieldHelp:      el.getAttribute('data-form-field-help') || '',
            elFormFieldRequired:  el.getAttribute('data-form-field-required') || '0',
            elInputType:          el.getAttribute('type') || '',
            elVeSc:               sentinelEl ? sentinelEl.getAttribute('data-ve-sc') : '',
            elVeScNodeId:         sentinelEl ? sentinelEl.id : '',
            styles: {
                'font-size':        cs.fontSize,
                'font-weight':      cs.fontWeight,
                'font-family':      cs.fontFamily,
                'color':            cs.color,
                'text-align':       cs.textAlign,
                'line-height':      cs.lineHeight,
                'padding-top':      cs.paddingTop,
                'padding-right':    cs.paddingRight,
                'padding-bottom':   cs.paddingBottom,
                'padding-left':     cs.paddingLeft,
                'margin-top':       cs.marginTop,
                'margin-right':     cs.marginRight,
                'margin-bottom':    cs.marginBottom,
                'margin-left':      cs.marginLeft,
                'background-color': cs.backgroundColor,
                'opacity':          cs.opacity,
                'border-width':     cs.borderWidth,
                'border-style':     cs.borderStyle,
                'border-color':     cs.borderColor,
                'border-radius':    cs.borderRadius,
                'width':            cs.width,
                'height':           cs.height,
                'display':          cs.display,
            },
        }, '*');

        // Send element path separately (for breadcrumb)
        window.parent.postMessage({ type: 've-element-path', path: elPath }, '*');

        // Send dimensions to parent
        var selRect = el.getBoundingClientRect();
        window.parent.postMessage({
            type: 've-element-dims',
            width: Math.round(selRect.width),
            height: Math.round(selRect.height),
            x: Math.round(selRect.left),
            y: Math.round(selRect.top)
        }, '*');

        // ── Show link popover when selecting an anchor ────────────────
        // First clear any previous link-active marker
        try {
            var prev = document.querySelector('[data-ve-link-active]');
            if (prev) prev.removeAttribute('data-ve-link-active');
        } catch (e) {}
        if (el.tagName === 'A') {
            el.setAttribute('data-ve-link-active', '1');
            // Get iframe-relative rect and convert to window-absolute via parent
            var iframeRect = el.getBoundingClientRect();
            window.parent.postMessage({
                type: 've-show-link-popover',
                href:  el.getAttribute('href') || '',
                attrs: {
                    target:   el.getAttribute('target') || '',
                    rel:      el.getAttribute('rel')    || '',
                    download: el.hasAttribute('download') ? (el.getAttribute('download') || '') : undefined
                },
                rect: {
                    top:    iframeRect.top,
                    left:   iframeRect.left,
                    right:  iframeRect.right,
                    bottom: iframeRect.bottom,
                    width:  iframeRect.width,
                    height: iframeRect.height
                },
                nodeId: selectedId
            }, '*');
        } else {
            window.parent.postMessage({ type: 've-hide-link-popover' }, '*');
        }

        // Show dims tooltip in iframe
        var dimsTooltip = document.getElementById('ve-dims-tooltip');
        if (!dimsTooltip) {
            dimsTooltip = document.createElement('div');
            dimsTooltip.id = 've-dims-tooltip';
            dimsTooltip.style.cssText = 'position:fixed;background:rgba(30,30,46,0.9);color:#fff;font-size:10px;padding:3px 8px;border-radius:4px;z-index:9999;pointer-events:none;white-space:nowrap;font-family:monospace;';
            document.body.appendChild(dimsTooltip);
        }
        dimsTooltip.textContent = Math.round(selRect.width) + ' × ' + Math.round(selRect.height) + 'px';
        dimsTooltip.style.display = 'block';
        dimsTooltip.style.left = (selRect.left + selRect.width / 2 - 40) + 'px';
        dimsTooltip.style.top  = Math.max(selRect.top - 26, 4) + 'px';
        clearTimeout(dimsTooltip._timer);
        dimsTooltip._timer = setTimeout(function() { dimsTooltip.style.display = 'none'; }, 3000);
    });

    // ── Double-click on sentinel → open block editor ─────────────────────
    document.addEventListener('dblclick', function (e) {
        if (isEditing) return;
        var el = e.target;
        var cur = el;
        while (cur && cur !== document.body) {
            if (cur.hasAttribute('data-ve-sc')) {
                window.parent.postMessage({ type: 've-open-sc-editor' }, '*');
                return;
            }
            cur = cur.parentElement;
        }
    });

    // ── U2: Paste cleaner (auto-clean Word/Docs HTML on paste) ──────────
    document.addEventListener('paste', function(e) {
        if (!isEditing || !editingEl) return;
        var html = (e.clipboardData || window.clipboardData).getData('text/html');
        if (!html) return;
        // Check if it's from MS Word or Google Docs
        if (html.indexOf('mso-') !== -1 || html.indexOf('docs-internal-guid') !== -1 || html.indexOf('MsoNormal') !== -1) {
            e.preventDefault();
            html = html.replace(/class="Mso[^"]*"/gi, '');
            html = html.replace(/style="[^"]*mso-[^"]*"/gi, '');
            html = html.replace(/<o:p>[\s\S]*?<\/o:p>/gi, '');
            html = html.replace(/<xml>[\s\S]*?<\/xml>/gi, '');
            html = html.replace(/<style>[\s\S]*?<\/style>/gi, '');
            html = html.replace(/<!--\[if[\s\S]*?endif\]-->/gi, '');
            html = html.replace(/<\/?span[^>]*>/gi, '');
            html = html.replace(/docs-internal-guid-[^"']*/gi, '');
            html = html.replace(/\s{2,}/g, ' ');
            document.execCommand('insertHTML', false, html);
        }
    });

    // ── Double-click to inline-edit text elements ────────────────────────
    var EDITABLE_TAGS = ['p','h1','h2','h3','h4','h5','h6','li','td','th','span','a','blockquote','button','label','div'];

    document.addEventListener('dblclick', function (e) {
        if (isEditing) return;

        var el  = e.target;
        var tag = el ? el.tagName.toLowerCase() : '';
        if (!el || !EDITABLE_TAGS.includes(tag)) return;
        if (el === document.body || el === document.documentElement) return;

        e.preventDefault();
        e.stopPropagation();

        if (!el.id) el.id = generateId();

        if (selectedEl && selectedEl !== el) {
            selectedEl.classList.remove('ve-sel', 've-ctx', 've-multi');
        }
        selectedEl = el;
        selectedId = el.id;

        el.contentEditable = 'true';
        el.focus();
        isEditing = true;
        editingEl = el;

        hideQuickBar();
        positionNearElement(toolbar, el);
        window.parent.postMessage({ type: 've-editing-started', nodeId: el.id }, '*');
    });

    // E4: Double-click on image → open MediaPicker in parent
    document.addEventListener('dblclick', function (e) {
        var el = e.target;
        if (!el || el.tagName.toLowerCase() !== 'img') return;
        e.preventDefault();
        e.stopPropagation();
        if (!el.id) el.id = generateId();
        selectedEl = el;
        selectedId = el.id;
        window.parent.postMessage({ type: 've-open-media-picker', nodeId: el.id, currentSrc: el.src }, '*');
    });

    // E5: Double-click on icon <i> → open Icon Picker in parent
    document.addEventListener('dblclick', function (e) {
        var el = e.target;
        // Match <i> or <svg> (for icon fonts and inline SVG icons)
        var tag = el ? el.tagName.toLowerCase() : '';
        if (!el || (tag !== 'i' && tag !== 'svg')) return;
        // Only treat as icon if has icon class (fa-*, material-icons, etc.) or parent suggests icon
        var cls = (typeof el.className === 'string' ? el.className : (el.getAttribute('class') || ''));
        var isIcon = /(^|\s)(fa-|material-icons|icon-|bi-|fas|far|fab|fal|fad|fa-solid|fa-regular|fa-brands)/i.test(cls);
        if (!isIcon) return;
        e.preventDefault();
        e.stopPropagation();
        if (!el.id) el.id = generateId();
        selectedEl = el;
        selectedId = el.id;
        window.parent.postMessage({
            type: 've-open-icon-picker',
            nodeId: el.id,
            currentClass: cls
        }, '*');
    });

    // E6: Image resize handles
    document.addEventListener('click', function (e) {
        // Remove existing resize handles
        document.querySelectorAll('.ve-img-resize-handle').forEach(function(h) { h.remove(); });
        var el = e.target;
        if (!el || el.tagName.toLowerCase() !== 'img') return;
        if (!el.id) el.id = generateId();

        // Add resize handles
        var wrapper = el.parentNode;
        if (wrapper.style.position !== 'relative') wrapper.style.position = 'relative';

        var handle = document.createElement('div');
        handle.className = 've-img-resize-handle';
        handle.style.cssText = 'position:absolute;bottom:0;right:0;width:14px;height:14px;background:#b10100;cursor:se-resize;border-radius:2px;z-index:9999;';
        var imgRect = el.getBoundingClientRect();
        var wrapRect = wrapper.getBoundingClientRect();
        handle.style.top = (imgRect.bottom - wrapRect.top - 14) + 'px';
        handle.style.left = (imgRect.right - wrapRect.left - 14) + 'px';
        wrapper.appendChild(handle);

        var startW, startH, startX, startY;
        handle.addEventListener('mousedown', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            startW = el.offsetWidth;
            startH = el.offsetHeight;
            startX = ev.clientX;
            startY = ev.clientY;

            function onMove(me) {
                var newW = startW + (me.clientX - startX);
                var newH = startH + (me.clientY - startY);
                el.style.width = Math.max(30, newW) + 'px';
                el.style.height = 'auto';
            }
            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
            }
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }, true);

    // ── Blur: commit edit when focus leaves editable + toolbar/popover ───
    document.addEventListener('focusout', function (e) {
        if (!isEditing) return;
        var related = e.relatedTarget;
        if (related && (toolbar.contains(related) || linkPopover.contains(related))) return;
        // Small delay to allow toolbar click to fire first
        setTimeout(function () {
            if (isEditing) commitInlineEdit();
        }, 150);
    });

    // ── Scroll sync ──────────────────────────────────────────────────────
    var _scrollTimer;
    document.addEventListener('scroll', function () {
        clearTimeout(_scrollTimer);
        _scrollTimer = setTimeout(function () {
            var children = Array.from(document.body.children).filter(function (el) {
                return el.tagName !== 'SCRIPT' && el.id !== 've-inline-toolbar'
                    && el.id !== 've-link-popover' && el.id !== 've-iframe-drop-ind';
            });
            var bestIdx = 0, bestScore = Infinity;
            children.forEach(function (el, i) {
                var r = el.getBoundingClientRect();
                var score = Math.abs(r.top - 80);
                if (score < bestScore) { bestScore = score; bestIdx = i; }
            });
            window.parent.postMessage({ type: 've-scroll-sync', index: bestIdx }, '*');
        }, 100);
    }, { passive: true });

    // ── Right-click context menu ─────────────────────────────────────────
    document.addEventListener('contextmenu', function (e) {
        var el = e.target;
        if (!el || el === document.body || el === document.documentElement) return;
        e.preventDefault();

        if (!el.id) el.id = generateId();

        if (selectedEl && selectedEl !== el) {
            selectedEl.classList.remove('ve-sel', 've-ctx', 've-multi');
        }
        selectedEl = el;
        selectedId = el.id;
        selectedEl.classList.add('ve-ctx');

        window.parent.postMessage({
            type:   've-context-menu',
            nodeId: selectedId,
            tag:    el.tagName.toLowerCase(),
            x:      e.clientX,
            y:      e.clientY,
        }, '*');
    });

})();
</script>
HTML;
    }

    /**
     * Resolve the Blade view for the page using the active theme.
     *
     * Mirrors the logic in PublicController and PreviewController.
     */
    /**
     * Build the HTML-safe value for a data-ve-sc attribute, reconstructed from the
     * shortcode key, parsed attrs, and inner content.  Brackets are replaced with
     * HTML entities so the shortcode compiler regex does not match inside the attribute.
     *
     * @param  array<string, string>  $attrs
     */
    public static function buildVeScTag(string $key, array $attrs, string $content): string
    {
        $tag = '['.$key;
        foreach ($attrs as $k => $v) {
            $tag .= ' '.$k.'="'.htmlspecialchars($v, ENT_QUOTES).'"';
        }
        $tag .= $content !== '' ? ']'.$content.'[/'.$key.']' : ' /]';

        return str_replace(['[', ']'], ['&#91;', '&#93;'], htmlspecialchars($tag, ENT_QUOTES));
    }

    private function resolveViewName(Page $page): string
    {
        $template = $page->template ?: 'default';

        $candidates = [
            $this->templateManager->getThemeViewPath("templates.{$template}"),
            $this->templateManager->getThemeViewPath('templates.default'),
            $this->templateManager->getThemeViewPath('page'),
        ];

        if ($template === 'homepage') {
            array_unshift($candidates, $this->templateManager->getThemeViewPath('index'));
        }

        foreach ($candidates as $candidate) {
            if (view()->exists($candidate)) {
                return $candidate;
            }
        }

        abort(404);
    }
}
