<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\Page\Events\PagePublished;
use Modules\Page\Http\Requests\CreatePageRequest;
use Modules\Page\Http\Requests\UpdatePageRequest;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageCategory;
use Modules\Page\Services\PageService;
use Modules\Template\Services\TemplateManager;

class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pageService,
        private readonly TemplateManager $templateManager,
    ) {}

    /**
     * Display a listing of pages.pages.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Page::class);

        $trashed = $request->boolean('trashed');

        $filters = [
            'status' => $request->get('status'),
            'search' => $request->get('search'),
            'template' => $request->get('template'),
            'category' => $request->get('category'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_order' => $request->get('sort_order', 'desc'),
            'per_page' => $request->get('per_page', config('page.per_page', 20)),
        ];

        $pages = $trashed
            ? $this->pageService->getTrashedPages($filters)
            : $this->pageService->getPages($filters);

        $allCategories = PageCategory::ordered()->get();

        $stats = $this->pageService->getStatsCache();

        return view('page::pages.pages.index', compact('pages', 'filters', 'stats', 'allCategories', 'trashed'));
    }

    /**
     * Show the form for creating a new page.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $this->authorize('create', Page::class);

        $page = new Page;
        $templates = $this->templateManager->getPageTemplates();
        $statuses = Page::getStatuses();
        $locales = PageService::getSupportedLocales();
        $categories = PageCategory::ordered()->get();

        return view('page::pages.pages.create', compact('page', 'templates', 'statuses', 'locales', 'categories'));
    }

    /**
     * Store a newly created page in storage.
     */
    public function store(CreatePageRequest $request): RedirectResponse
    {
        $this->authorize('create', Page::class);

        try {
            $data = $request->validated();

            if ($request->hasFile('featured_image')) {
                $data['featured_image'] = $request->file('featured_image');
            }

            $page = $this->pageService->createPage($data);

            return redirect()
                ->route('pages.edit', $page->id)
                ->with('success', 'Página creada exitosamente.');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error al crear la página: '.$e->getMessage());
        }
    }

    /**
     * Display the specified page.
     */
    public function show(Page $page): RedirectResponse
    {
        $this->authorize('view', $page);

        return redirect()->route('pages.edit', $page->id);
    }

    /**
     * Show the form for editing the specified page.
     *
     * @return \Illuminate\View\View
     */
    public function edit(Page $page): View
    {
        $this->authorize('update', $page);

        $page->load(['translations', 'categories', 'tags']);

        $templates = $this->templateManager->getPageTemplates();
        $statuses = Page::getStatuses();
        $locales = PageService::getSupportedLocales();
        $translations = $page->translations->keyBy('locale');
        $categories = PageCategory::ordered()->get();

        return view('page::pages.pages.edit', compact('page', 'templates', 'statuses', 'locales', 'translations', 'categories'));
    }

    /**
     * Update the specified page in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePageRequest $request, Page $page): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $page);

        try {
            $data = $request->validated();

            if ($request->hasFile('featured_image')) {
                $data['featured_image'] = $request->file('featured_image');
            }

            $page = $this->pageService->updatePage($page, $data);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Página actualizada exitosamente.',
                ]);
            }

            return redirect()
                ->route('pages.edit', $page->id)
                ->with('success', 'Página actualizada exitosamente.');
        } catch (Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar la página: '.$e->getMessage(),
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la página: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified page from storage.
     */
    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);

        try {
            $this->pageService->deletePage($page);

            return redirect()
                ->route('pages.index')
                ->with('success', 'Página eliminada exitosamente.');
        } catch (Exception $e) {
            return back()
                ->with('error', 'Error al eliminar la página: '.$e->getMessage());
        }
    }

    /**
     * Publish a page.
     */
    public function publish(Page $page): RedirectResponse
    {
        $this->authorize('publish', $page);

        try {
            $this->pageService->publishPage($page);

            // Dispatch event to warm cache
            PagePublished::dispatch($page);

            return back()->with('success', 'Página publicada exitosamente.');
        } catch (Exception $e) {
            return back()->with('error', 'Error al publicar la página: '.$e->getMessage());
        }
    }

    /**
     * Unpublish a page.
     */
    public function unpublish(Page $page): RedirectResponse
    {
        $this->authorize('publish', $page);

        try {
            $this->pageService->unpublishPage($page);

            return back()->with('success', 'Página despublicada exitosamente.');
        } catch (Exception $e) {
            return back()->with('error', 'Error al despublicar la página: '.$e->getMessage());
        }
    }

    /**
     * Duplicate a page.
     */
    public function duplicate(Page $page): RedirectResponse
    {
        $this->authorize('duplicate', $page);

        try {
            $newPage = $this->pageService->duplicatePage($page);

            return redirect()
                ->route('pages.edit', $newPage->id)
                ->with('success', 'Página duplicada exitosamente.');
        } catch (Exception $e) {
            return back()->with('error', 'Error al duplicar la página: '.$e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted page.
     *
     * @param  int  $id
     */
    public function restore($id): RedirectResponse
    {
        $page = Page::withTrashed()->findOrFail($id);

        $this->authorize('restore', $page);

        try {
            $this->pageService->restorePage($page);

            return redirect()
                ->route('pages.index')
                ->with('success', 'Página restaurada exitosamente.');
        } catch (Exception $e) {
            return back()->with('error', 'Error al restaurar la página: '.$e->getMessage());
        }
    }

    /**
     * Force delete a page permanently.
     *
     * @param  int  $id
     */
    public function forceDelete($id): RedirectResponse
    {
        $page = Page::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $page);

        try {
            $this->pageService->forceDeletePage($page);

            return redirect()
                ->route('pages.index')
                ->with('success', 'Página eliminada permanentemente.');
        } catch (Exception $e) {
            return back()->with('error', 'Error al eliminar la página: '.$e->getMessage());
        }
    }

    /**
     * Perform bulk action on pages.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('bulkAction', Page::class);

        $request->validate([
            'action' => ['required', 'string', Rule::in(['publish', 'unpublish', 'delete', 'restore'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        $pages = $action === 'restore'
            ? Page::onlyTrashed()->whereIn('id', $ids)->get()
            : Page::whereIn('id', $ids)->get();

        $count = 0;

        foreach ($pages as $page) {
            try {
                match ($action) {
                    'publish', 'unpublish' => $this->authorize('update', $page),
                    'delete' => $this->authorize('delete', $page),
                    'restore' => $this->authorize('restore', $page),
                };

                match ($action) {
                    'publish' => $this->pageService->publishPage($page),
                    'unpublish' => $this->pageService->unpublishPage($page),
                    'delete' => $this->pageService->deletePage($page),
                    'restore' => $this->pageService->restorePage($page),
                };

                $count++;
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                Log::warning("Bulk action '{$action}' denied for page {$page->id}: ".$e->getMessage());
            } catch (Exception $e) {
                Log::warning("Bulk action '{$action}' failed for page {$page->id}: ".$e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} página(s) procesadas correctamente.",
        ]);
    }

    /**
     * Show the analytics dashboard for a page.
     */
    public function analytics(Page $page): View
    {
        $this->authorize('update', $page);

        return view('page::pages.analytics', compact('page'));
    }

    /**
     * Quick search for admin autocomplete (AJAX).
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        $q = trim((string) $request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $pages = Page::query()
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            })
            ->when($request->boolean('include_trashed'), fn ($q) => $q->withTrashed())
            ->orderByDesc('published_at')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'status', 'published_at']);

        return response()->json(
            $pages->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'status' => $p->status instanceof \Modules\Page\Enums\PageStatus ? $p->status->value : $p->status,
                'url' => route('pages.edit', $p),
                'badge' => match ($p->status instanceof \Modules\Page\Enums\PageStatus ? $p->status->value : $p->status) {
                    'published' => '<span class="badge bg-success">Publicada</span>',
                    'draft' => '<span class="badge bg-secondary">Borrador</span>',
                    default => '<span class="badge bg-warning">Pendiente</span>',
                },
            ])
        );
    }

    /**
     * Generate a unique slug from a title via AJAX.
     */
    public function ajaxSlug(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'ignoreId' => ['nullable', 'integer'],
        ]);

        $slug = $this->pageService->generateUniqueSlug(
            $request->input('title'),
            ignoreId: $request->input('ignoreId')
        );

        return response()->json(['slug' => $slug]);
    }
}
