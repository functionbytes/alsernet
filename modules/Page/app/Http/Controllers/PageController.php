<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Page\Http\Requests\CreatePageRequest;
use Modules\Page\Http\Requests\UpdatePageRequest;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageService;

class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pageService
    ) {}

    /**
     * Display a listing of pages.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Page::class);

        $filters = [
            'status' => $request->get('status'),
            'search' => $request->get('search'),
            'template' => $request->get('template'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_order' => $request->get('sort_order', 'desc'),
            'per_page' => $request->get('per_page', config('page.per_page', 20)),
        ];

        $pages = $this->pageService->getPages($filters);

        return view('page::admin.index', compact('pages', 'filters'));
    }

    /**
     * Show the form for creating a new page.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $this->authorize('create', Page::class);

        $page = new Page;
        $templates = config('page.templates', ['default' => 'Default']);
        $statuses = Page::getStatuses();

        return view('page::admin.create', compact('page', 'templates', 'statuses'));
    }

    /**
     * Store a newly created page in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CreatePageRequest $request)
    {
        $this->authorize('create', Page::class);

        try {
            $data = $request->validated();

            // Handle file upload
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
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Page $page)
    {
        $this->authorize('view', $page);

        return redirect()->route('pages.edit', $page->id);
    }

    /**
     * Show the form for editing the specified page.
     *
     * @return \Illuminate\View\View
     */
    public function edit(Page $page)
    {
        $this->authorize('update', $page);

        $templates = config('page.templates', ['default' => 'Default']);
        $statuses = Page::getStatuses();

        return view('page::admin.edit', compact('page', 'templates', 'statuses'));
    }

    /**
     * Update the specified page in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePageRequest $request, Page $page)
    {
        $this->authorize('update', $page);

        try {
            $data = $request->validated();

            // Handle file upload
            if ($request->hasFile('featured_image')) {
                $data['featured_image'] = $request->file('featured_image');
            }

            $page = $this->pageService->updatePage($page, $data);

            return redirect()
                ->route('pages.edit', $page->id)
                ->with('success', 'Página actualizada exitosamente.');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la página: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified page from storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Page $page)
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
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function publish(Page $page)
    {
        $this->authorize('publish', $page);

        try {
            $this->pageService->publishPage($page);

            return back()->with('success', 'Página publicada exitosamente.');
        } catch (Exception $e) {
            return back()->with('error', 'Error al publicar la página: '.$e->getMessage());
        }
    }

    /**
     * Unpublish a page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unpublish(Page $page)
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
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate(Page $page)
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore($id)
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
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
     * Generate a unique slug from a title via AJAX.
     */
    public function ajaxSlug(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'ignoreId' => ['nullable', 'integer'],
        ]);

        $slug = $this->pageService->generateSlug(
            $request->input('title'),
            $request->input('ignoreId')
        );

        return response()->json(['slug' => $slug]);
    }
}
