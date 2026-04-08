<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Attention\Imports\AttentionImport;
use Modules\Forms\Imports\FormSubmissionImport;
use Modules\Helpdesk\Imports\HelpdeskTicketImport;
use Modules\Reviews\Imports\ReviewImport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    /** @var array<string, class-string> */
    private array $importers = [
        'reviews' => ReviewImport::class,
        'helpdesk' => HelpdeskTicketImport::class,
        'attention' => AttentionImport::class,
        'forms' => FormSubmissionImport::class,
    ];

    /** @var array<string, string[]> */
    private array $templateHeaders = [
        'reviews' => ['reviewer_name', 'star_rating', 'comment', 'review_time', 'google_reply_text'],
        'helpdesk' => ['title', 'description', 'status', 'priority', 'category', 'department'],
        'attention' => ['subject', 'description', 'customer_firstname', 'customer_lastname', 'customer_email', 'customer_cellphone', 'customer_dni', 'status'],
        'forms' => ['form_id', 'status', 'ip_address', 'country', 'city', 'utm_source', 'utm_medium', 'utm_campaign'],
    ];

    public function show(string $type): View
    {
        abort_unless(array_key_exists($type, $this->importers), 404);

        return view('imports.show', compact('type'));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        abort_unless(array_key_exists($type, $this->importers), 404);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $importerClass = $this->importers[$type];
        $importer = new $importerClass;

        Excel::import($importer, $request->file('file'));

        $imported = $importer->getImportedCount();
        $skipped = $importer->getSkippedCount();

        $message = "Importacion completada: {$imported} registros importados";
        if ($skipped > 0) {
            $message .= ", {$skipped} omitidos por errores";
        }

        return back()
            ->with('success', $message)
            ->with('import_skipped', $importer->getSkipped());
    }

    public function template(string $type): BinaryFileResponse
    {
        abort_unless(array_key_exists($type, $this->templateHeaders), 404);

        $headers = $this->templateHeaders[$type];
        $filename = "plantilla-importacion-{$type}.csv";

        $handle = tmpfile();

        abort_if($handle === false, 500, 'No se pudo crear el archivo temporal.');

        fputcsv($handle, $headers);

        $tmpPath = stream_get_meta_data($handle)['uri'];

        return response()->download($tmpPath, $filename, ['Content-Type' => 'text/csv'])
            ->deleteFileAfterSend(false);
    }
}
