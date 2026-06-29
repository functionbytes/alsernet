<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Social\Http\Requests\StoreBulkImportRequest;
use Modules\Social\Jobs\ProcessBulkImportJob;
use Modules\Social\Models\BulkImport;

class BulkImportController extends Controller
{
    public function index()
    {
        $imports = BulkImport::where('account_id', auth()->user()->account_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('social::bulk-import.index', compact('imports'));
    }

    public function create()
    {
        return view('social::bulk-import.create');
    }

    public function store(StoreBulkImportRequest $request)
    {
        $file = $request->file('file');
        $path = $file->store('bulk-imports/'.auth()->user()->account_id, 'local');

        $import = BulkImport::create([
            ...$request->validated(),
            'file_path' => $path,
        ]);

        ProcessBulkImportJob::dispatch($import);

        return redirect()
            ->route('admin.social.bulk-import.index')
            ->with('success', 'Importación en proceso. Te notificaremos cuando esté completa.');
    }

    public function show(BulkImport $bulkImport)
    {
        $this->authorize('view', $bulkImport);

        return view('social::bulk-import.show', compact('bulkImport'));
    }

    public function destroy(BulkImport $bulkImport)
    {
        $this->authorize('delete', $bulkImport);

        $bulkImport->delete();

        return redirect()
            ->route('admin.social.bulk-import.index')
            ->with('success', 'Importación eliminada exitosamente');
    }
}
