<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            return back()
                ->with('error', 'Error al restaurar la versión: '.$e->getMessage());
        }
    }

    /**
     * Compare two versions.
     *
     * @return \Illuminate\View\View
     */
    public function compare(Request $request, Page $page): View|RedirectResponse
    {
        $request->validate([
            'version1' => 'required|exists:page_versions,id',
            'version2' => 'required|exists:page_versions,id',
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
            return back()
                ->with('error', 'Error al comparar versiones: '.$e->getMessage());
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
            return back()
                ->with('error', 'Error al eliminar la versión: '.$e->getMessage());
        }
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
            return back()
                ->with('error', 'Error al crear la versión: '.$e->getMessage());
        }
    }
}
