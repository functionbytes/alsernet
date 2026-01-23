<?php

namespace Modules\Mailrelay\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEmailImportJob;
use Illuminate\Http\Request;
use Modules\Mailrelay\Entities\ImportJob;

class ImportWebController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $imports = ImportJob::latest()->paginate(15);

        return view('mailrelay::imports.index', compact('imports'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('mailrelay::imports.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
            'list_id' => 'nullable|exists:mails_lists,id',
            'validate_emails' => 'nullable|boolean',
        ]);

        // Store the uploaded file
        $file = $request->file('file');
        $path = $file->store('imports');

        // Create import job
        $importJob = ImportJob::create([
            'filename' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        // Dispatch processing job
        ProcessEmailImportJob::dispatch(
            $importJob->id,
            $path,
            $validated['list_id'] ?? null,
            $validated['validate_emails'] ?? true
        );

        return redirect()->route('imports.index')
            ->with('success', 'Import started successfully. Processing in background.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $import = ImportJob::findOrFail($id);

        return view('mailrelay::imports.show', compact('import'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $import = ImportJob::findOrFail($id);

        return view('mailrelay::imports.edit', compact('import'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Not applicable for imports

        return redirect()->route('imports.index')
            ->with('info', 'Imports cannot be edited.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $import = ImportJob::findOrFail($id);
        $import->delete();

        return redirect()->route('imports.index')
            ->with('success', 'Import deleted successfully.');
    }
}
