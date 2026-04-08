<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageVersion;

class PageVersionController extends Controller
{
    /**
     * Display version history for a page.
     */
    public function index(Page $page): View
    {
        $this->authorize('update', $page);

        $versions = $page->getVersionHistory(100);

        return view('page::pages.versions.index', compact('page', 'versions'));
    }

    /**
     * Display a specific version.
     */
    public function show(Page $page, PageVersion $version): View
    {
        // Ensure the version belongs to the page
        if ($version->page_id !== $page->id) {
            abort(404);
        }

        $version->load('user');

        return view('page::pages.versions.show', compact('page', 'version'));
    }

    /**
     * Restore a specific version.
     */
    public function restore(Request $request, Page $page, PageVersion $version): RedirectResponse
    {
        $this->authorize('update', $page);

        // Ensure the version belongs to the page
        if ($version->page_id !== $page->id) {
            abort(404);
        }

        try {
            DB::beginTransaction();

            $page->restoreVersion($version->id);

            DB::commit();

            return redirect()
                ->route('pages.edit', $page->id)
                ->with('success', "Versión {$version->version_number} restaurada exitosamente.");
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('PageVersion restore failed', ['error' => $e->getMessage()]);

            return back()
                ->with('error', 'Error al restaurar la versión. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Compare two versions.
     *
     * @return View
     */
    public function compare(Request $request, Page $page): View|RedirectResponse
    {
        $this->authorize('update', $page);

        $request->validate([
            'version1' => ['required', Rule::exists('page_versions', 'id')->where('page_id', $page->id)],
            'version2' => ['required', Rule::exists('page_versions', 'id')->where('page_id', $page->id)],
        ]);

        try {
            $comparison = $page->compareVersions(
                $request->input('version1'),
                $request->input('version2')
            );

            return view('page::pages.versions.compare', array_merge(
                compact('page'),
                $comparison
            ));
        } catch (Exception $e) {
            Log::error('PageVersion compare failed', ['error' => $e->getMessage()]);

            return back()
                ->with('error', 'Error al comparar versiones. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Delete a specific version.
     */
    public function destroy(Page $page, PageVersion $version): RedirectResponse
    {
        $this->authorize('delete', $page);

        // Ensure the version belongs to the page
        if ($version->page_id !== $page->id) {
            abort(404);
        }

        // Prevent deleting the only version
        if ($page->getTotalVersions() <= 1) {
            return back()
                ->with('error', 'No se puede eliminar la única versión disponible.');
        }

        try {
            $versionNumber = $version->version_number;
            $version->delete();

            return redirect()
                ->route('pages.versions.index', $page->id)
                ->with('success', "Versión {$versionNumber} eliminada exitosamente.");
        } catch (Exception $e) {
            Log::error('PageVersion delete failed', ['error' => $e->getMessage()]);

            return back()
                ->with('error', 'Error al eliminar la versión. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Bulk action on versions (delete).
     */
    public function bulkAction(Request $request, Page $page): JsonResponse
    {
        $this->authorize('delete', $page);

        $validated = $request->validate([
            'action' => 'required|in:delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $latestVersion = $page->versions()->latest('version_number')->first();

        $query = PageVersion::where('page_id', $page->id)
            ->whereIn('id', $validated['ids']);

        if ($latestVersion) {
            $query->where('id', '!=', $latestVersion->id);
        }

        $count = $query->delete();

        return response()->json([
            'message' => $count.' versión(es) eliminadas.',
            'count' => $count,
        ]);
    }

    /**
     * Create a manual version snapshot.
     */
    public function create(Request $request, Page $page): RedirectResponse
    {
        try {
            $version = $page->createVersion();

            return back()
                ->with('success', "Versión {$version->version_number} creada exitosamente.");
        } catch (Exception $e) {
            Log::error('PageVersion create failed', ['error' => $e->getMessage()]);

            return back()
                ->with('error', 'Error al crear la versión. Por favor, inténtalo de nuevo.');
        }
    }
}
