<?php

namespace App\Listeners\Return;

use App\Events\Return\ReturnCreated;
use App\Services\ReturnPDFService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GeneratePDFListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $pdfService;

    public function __construct(ReturnPDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReturnCreated $event): void
    {
        try {
            // Solo generar PDF si está configurado para hacerlo
            if (!$event->shouldGeneratePDF()) {
                Log::info('PDF generation skipped', [
                    'return_id' => $event->return->id_return_request,
                    'reason' => 'Configuration or conditions not met'
                ]);
                return;
            }

            Log::info('Starting PDF generation', [
                'return_id' => $event->return->id_return_request,
                'created_by' => $event->createdBy
            ]);

            // Generar y guardar PDF
            $pdfPath = $this->pdfService->generateAndSaveReturnPDF($event->return);

            // Actualizar la devolución con la ruta del PDF
            $event->return->update(['pdf_path' => $pdfPath]);

            Log::info('PDF generated successfully', [
                'return_id' => $event->return->id_return_request,
                'pdf_path' => $pdfPath,
                'file_size' => $this->getFileSize($pdfPath)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate PDF', [
                'return_id' => $event->return->id_return_request,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-lanzar la excepción para que Laravel retry el job si está en cola
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ReturnCreated $event, \Throwable $exception): void
    {
        Log::critical('PDF generation failed permanently', [
            'return_id' => $event->return->id_return_request,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts ?? 'unknown'
        ]);

        // Opcional: Notificar a los administradores sobre el fallo
        // \Notification::route('mail', config('returns.admin_email'))
        //     ->notify(new PDFGenerationFailedNotification($event->return, $exception));
    }

    /**
     * Obtener el tamaño del archivo generado
     */
    private function getFileSize(string $path): ?int
    {
        try {
            $fullPath = storage_path('app/public/' . $path);
            return file_exists($fullPath) ? filesize($fullPath) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determinar si el listener debe ser ejecutado
     */
    public function shouldQueue(ReturnCreated $event): bool
    {
        return $event->shouldGeneratePDF();
    }
}
