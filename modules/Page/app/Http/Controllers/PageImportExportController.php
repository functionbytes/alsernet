<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Page\Models\Page;
use Modules\Page\Services\PageExportService;
use Modules\Page\Services\PageImportService;

class PageImportExportController extends Controller
{
    public function __construct(
        private readonly PageExportService $exportService,
        private readonly PageImportService $importService,
    ) {}

    /**
     * Show the export form.
     * GET /pages/export
     */
    public function exportForm(): View
    {
        $this->authorize('viewAny', Page::class);

        return view('page::pages.export');
    }

    /**
     * Export pages as CSV or JSON.
     * POST /pages/export
     */
    public function export(Request $request): mixed
    {
        $this->authorize('viewAny', Page::class);

        $pages = Page::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->cursor();

        return match ($request->get('format', 'csv')) {
            'json' => $this->exportService->exportJson($pages),
            default => $this->exportService->exportCsv($pages),
        };
    }

    /**
     * Show the import form.
     * GET /pages/import
     */
    public function importForm(): View
    {
        $this->authorize('create', Page::class);

        return view('page::pages.import');
    }

    /**
     * Process the uploaded import file.
     * POST /pages/import
     */
    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Page::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,json', 'max:10240'],
            'format' => ['required', 'in:csv,json'],
        ]);

        $result = match ($request->format) {
            'json' => $this->importService->importFromJson($request->file('file')),
            default => $this->importService->importFromCsv($request->file('file')),
        };

        if (! empty($result['errors'])) {
            session()->flash('import_errors', $result['errors']);
        }

        return redirect()
            ->route('pages.index')
            ->with('success', "Importación completada: {$result['imported']} páginas importadas, {$result['skipped']} omitidas.");
    }
}
