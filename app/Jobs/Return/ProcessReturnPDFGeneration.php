<?php

namespace App\Jobs\Return;

use App\Models\Return\ReturnRequest;
use App\Services\Return\ReturnPDFService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessReturnPDFGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $returnRequest;
    public $tries = 3;
    public $timeout = 120;
    public $backoff = [10, 30, 60]; // Reintentos con delay progresivo

    public function __construct(ReturnRequest $returnRequest)
    {
        $this->returnRequest = $returnRequest;
        $this->onQueue('pdf-generation'); // Cola específica para PDFs
    }

    public function handle(ReturnPDFService $pdfService)
    {
        Log::info('Iniciando generación de PDF', [
            'return_id' => $this->returnRequest->id_return_request,
            'customer_email' => $this->returnRequest->email
        ]);

        try {
            // Generar y guardar PDF
            $pdfPath = $pdfService->generateAndSaveReturnPDF($this->returnRequest);

            // Actualizar el registro con la ruta del PDF
            $this->returnRequest->update(['pdf_path' => $pdfPath]);

            Log::info('PDF generado exitosamente', [
                'return_id' => $this->returnRequest->id_return_request,
                'pdf_path' => $pdfPath
            ]);

            // Opcional: Disparar evento de PDF completado
            event(new \App\Events\ReturnPDFGenerated($this->returnRequest, $pdfPath));

        } catch (\Exception $e) {
            Log::error('Error generando PDF', [
                'return_id' => $this->returnRequest->id_return_request,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Si es el último intento, marcar como fallido
            if ($this->attempts() >= $this->tries) {
                Log::critical('PDF generation failed after all retries', [
                    'return_id' => $this->returnRequest->id_return_request,
                    'attempts' => $this->attempts()
                ]);

                // Opcional: Notificar a administradores
                event(new \App\Events\ReturnPDFGenerationFailed($this->returnRequest, $e));
            }

            throw $e; // Re-lanzar para que Laravel maneje el reintento
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Job de generación PDF falló definitivamente', [
            'return_id' => $this->returnRequest->id_return_request,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Marcar en la base de datos que la generación falló
        $this->returnRequest->update([
            'pdf_generation_failed' => true,
            'pdf_generation_error' => $exception->getMessage()
        ]);
    }

    public function retryUntil()
    {
        return now()->addMinutes(30); // Reintentar hasta 30 minutos
    }

    public function uniqueId()
    {
        return $this->returnRequest->id_return_request;
    }
}
