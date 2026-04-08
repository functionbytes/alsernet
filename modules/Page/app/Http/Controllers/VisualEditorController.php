<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Page\Events\PageAutoSaved;
use Modules\Page\Models\Page;
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
        $publicUrl = url($prefix ? $prefix.'/'.$page->slug : $page->slug);

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

        $transContent = function_exists('shortcode') ? shortcode($tempContent) : $tempContent;

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

        $data = $request->validate([
            'locale' => ['required', 'string', 'max:10'],
            'content' => ['nullable', 'string'],
            'template' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
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
            $page->translations()->updateOrCreate(
                ['locale' => $locale],
                $translationFields
            );
        }

        return response()->json(['success' => true, 'message' => 'Página guardada correctamente.']);
    }

    /**
     * Auto-save draft from the visual editor (web route, session auth).
     */
    public function autoSave(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $data = $request->validate([
            'content' => ['nullable', 'string'],
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

        $data = $request->validate(['shortcode' => ['required', 'string', 'max:500']]);

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
.ve-sel   { outline: 2px solid #1a1a1a !important; outline-offset: 1px !important; }
.ve-ctx   { outline: 2px solid #FA896B !important; outline-offset: 1px !important; }
.ve-multi { outline: 2px solid #fd7e14 !important; outline-offset: 1px !important; }
</style>
<script>
(function () {
    var selectedEl    = null;
    var selectedId    = null;
    var idCounter     = 0;
    var isEditing     = false;
    var editingEl     = null;
    var multiSelected = [];

    function generateId() {
        return 've-el-' + Date.now() + '-' + (++idCounter);
    }

    function cleanForExport() {
        // Remove CSS selection classes (current approach)
        document.querySelectorAll('.ve-sel,.ve-ctx,.ve-multi').forEach(function (el) {
            el.classList.remove('ve-sel', 've-ctx', 've-multi');
        });
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

    function extractContent() {
        var ck = document.querySelector('.ck-content');
        if (!ck) return document.body.innerHTML;
        // Remove auto-generated TOC before extracting
        var toc = ck.querySelector('.page-toc');
        if (toc) toc.remove();
        var html = ck.innerHTML;
        return html;
    }

    function commitInlineEdit() {
        if (!editingEl) return;
        editingEl.contentEditable = 'false';
        editingEl.removeAttribute('contenteditable');
        document.getElementById('ve-inline-toolbar').style.display = 'none';
        isEditing = false;
        editingEl = null;
        window.parent.postMessage({ type: 've-inline-edit-committed', html: extractContent() }, '*');
    }

    // ── Inject floating inline toolbar ──────────────────────────────────
    var toolbar = document.createElement('div');
    toolbar.id = 've-inline-toolbar';
    toolbar.style.cssText = 'display:none;position:fixed;z-index:99999;background:#1e1e2e;border-radius:6px;padding:4px 6px;gap:4px;align-items:center;box-shadow:0 4px 12px rgba(0,0,0,.4);';
    toolbar.innerHTML = [
        '<button data-cmd="bold" title="Negrita" style="background:transparent;border:1px solid #555;border-radius:4px;color:#fff;padding:2px 7px;cursor:pointer;font-weight:700;font-size:13px;">B</button>',
        '<button data-cmd="italic" title="Cursiva" style="background:transparent;border:1px solid #555;border-radius:4px;color:#fff;padding:2px 7px;cursor:pointer;font-style:italic;font-size:13px;">I</button>',
        '<button data-cmd="underline" title="Subrayado" style="background:transparent;border:1px solid #555;border-radius:4px;color:#fff;padding:2px 7px;cursor:pointer;text-decoration:underline;font-size:13px;">U</button>',
        '<span style="width:1px;background:#444;height:16px;display:inline-block;"></span>',
        '<button id="ve-tb-link" title="Enlace" style="background:transparent;border:1px solid #555;border-radius:4px;color:#fff;padding:2px 7px;cursor:pointer;font-size:13px;">&#128279;</button>',
        '<span style="width:1px;background:#444;height:16px;display:inline-block;"></span>',
        '<button id="ve-tb-done" title="Guardar cambios" style="background:#1a1a1a;border:1px solid #1a1a1a;border-radius:4px;color:#fff;padding:2px 8px;cursor:pointer;font-size:13px;font-weight:600;">&#10003;</button>',
    ].join('');
    document.body.appendChild(toolbar);

    // Prevent toolbar mousedown from stealing focus
    toolbar.addEventListener('mousedown', function (e) { e.preventDefault(); });

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
                        elPlaceholder: byIdEl.getAttribute('placeholder') || '',
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
                document.body.style.zoom = (d.zoom || 1);
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
        }
    });

    // ── Click to select element ──────────────────────────────────────────
    document.addEventListener('click', function (e) {
        if (isEditing) return;

        var el = e.target;
        if (!el || el === document.body || el === document.documentElement) return;
        e.preventDefault();
        e.stopPropagation();

        if (!el.id || el.id === '') el.id = generateId();

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

        var cs = window.getComputedStyle(el);

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
            elPlaceholder: el.getAttribute('placeholder') || '',
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

        positionNearElement(toolbar, el);
        window.parent.postMessage({ type: 've-editing-started', nodeId: el.id }, '*');
    });

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
