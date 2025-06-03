<?php

namespace App\Http\Controllers\Callcenters\Returns;

use App\Http\Controllers\Controller;
use App\Models\Return\ReturnPdfDocument;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReturnPdfDocumentController extends Controller
{
    protected $pdfGenerator;

    public function __construct(PdfGeneratorService $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
    }

    /**
     * Mostrar lista de documentos PDF
     */
    public function index(Request $request)
    {
        $query = ReturnPdfDocument::with('generatedBy')
            ->where('generated_by', Auth::id())
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('template')) {
            $query->where('template', $request->template);
        }

        $documents = $query->paginate(15);

        return view('pdf-documents.index', compact('documents'));
    }

    /**
     * Mostrar formulario para crear nuevo documento
     */
    public function create()
    {
        $templates = $this->getAvailableTemplates();
        return view('pdf-documents.create', compact('templates'));
    }

    /**
     * Generar nuevo documento PDF
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template' => 'required|string',
            'data' => 'nullable|array',
        ]);

        $document = ReturnPdfDocument::create([
            'title' => $request->title,
            'description' => $request->description,
            'template' => $request->template,
            'data' => $request->data ?? [],
            'generated_by' => Auth::id(),
            'status' => 'generating',
        ]);

        try {
            $this->generateReturnPdfDocument($document);

            return redirect()
                ->route('pdf-documents.show', $document)
                ->with('success', 'Documento PDF generado exitosamente.');
        } catch (\Exception $e) {
            $document->update(['status' => 'failed']);

            return redirect()
                ->route('pdf-documents.index')
                ->with('error', 'Error al generar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar documento específico
     */
    public function show(ReturnPdfDocument $ReturnPdfDocument)
    {
        $this->authorize('view', $ReturnPdfDocument);
        return view('pdf-documents.show', compact('ReturnPdfDocument'));
    }

    /**
     * Descargar documento PDF
     */
    public function download(ReturnPdfDocument $ReturnPdfDocument)
    {
        $this->authorize('view', $ReturnPdfDocument);

        if (!$ReturnPdfDocument->file_path || !Storage::exists($ReturnPdfDocument->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        return Storage::download(
            $ReturnPdfDocument->file_path,
            $ReturnPdfDocument->title . '.pdf'
        );
    }

    /**
     * Ver PDF en el navegador
     */
    public function preview(ReturnPdfDocument $ReturnPdfDocument)
    {
        $this->authorize('view', $ReturnPdfDocument);

        if (!$ReturnPdfDocument->file_path || !Storage::exists($ReturnPdfDocument->file_path)) {
            abort(404, 'Archivo no encontrado');
        }

        return response(Storage::get($ReturnPdfDocument->file_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $ReturnPdfDocument->title . '.pdf"'
        ]);
    }

    /**
     * Regenerar documento PDF
     */
    public function regenerate(ReturnPdfDocument $ReturnPdfDocument)
    {
        $this->authorize('update', $ReturnPdfDocument);

        try {
            $ReturnPdfDocument->update(['status' => 'generating']);
            $this->generateReturnPdfDocument($ReturnPdfDocument);

            return redirect()
                ->route('pdf-documents.show', $ReturnPdfDocument)
                ->with('success', 'Documento regenerado exitosamente.');
        } catch (\Exception $e) {
            $ReturnPdfDocument->update(['status' => 'failed']);

            return redirect()
                ->route('pdf-documents.show', $ReturnPdfDocument)
                ->with('error', 'Error al regenerar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar documento PDF
     */
    public function destroy(ReturnPdfDocument $ReturnPdfDocument)
    {
        $this->authorize('delete', $ReturnPdfDocument);

        if ($ReturnPdfDocument->file_path && Storage::exists($ReturnPdfDocument->file_path)) {
            Storage::delete($ReturnPdfDocument->file_path);
        }

        $ReturnPdfDocument->delete();

        return redirect()
            ->route('pdf-documents.index')
            ->with('success', 'Documento eliminado exitosamente.');
    }

    /**
     * Generar el archivo PDF
     */
    protected function generateReturnPdfDocument(ReturnPdfDocument $document)
    {
        $viewName = 'pdf-templates.' . $document->template;

        if (!view()->exists($viewName)) {
            throw new \Exception("Template '{$document->template}' no encontrado");
        }

        $pdf = $this->pdfGenerator->generateFromView($viewName, $document->data);

        $filename = Str::slug($document->title) . '_' . time() . '.pdf';
        $filePath = 'pdf-documents/' . $filename;

        $this->pdfGenerator->savePdf($pdf, $filePath);

        $document->update([
            'file_path' => $filePath,
            'file_size' => Storage::size($filePath),
            'status' => 'completed',
            'generated_at' => now(),
        ]);
    }

    /**
     * Obtener plantillas disponibles
     */
    protected function getAvailableTemplates(): array
    {
        return [
            'invoice' => 'Factura',
            'report' => 'Reporte',
            'certificate' => 'Certificado',
            'contract' => 'Contrato',
            'letter' => 'Carta',
        ];
    }
}
