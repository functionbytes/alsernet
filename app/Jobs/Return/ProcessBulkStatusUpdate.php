<?php

namespace App\Jobs\Return;

use App\Models\Return\ReturnRequest;
use App\Services\Return\ReturnService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessBulkStatusUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $returnIds;
    protected $newStatusId;
    protected $description;
    protected $employeeId;
    protected $batchId;

    public $tries = 3;
    public $timeout = 300; // 5 minutos para operaciones masivas

    public function __construct(array $returnIds, int $newStatusId, string $description, int $employeeId)
    {
        $this->returnIds = $returnIds;
        $this->newStatusId = $newStatusId;
        $this->description = $description;
        $this->employeeId = $employeeId;
        $this->batchId = uniqid('bulk_', true);
        $this->onQueue('bulk-operations');
    }

    public function handle(ReturnService $returnService)
    {
        Log::info('Iniciando actualización masiva de estados', [
            'batch_id' => $this->batchId,
            'return_count' => count($this->returnIds),
            'new_status_id' => $this->newStatusId,
            'employee_id' => $this->employeeId
        ]);

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($this->returnIds as $returnId) {
                try {
                    $returnService->updateReturnStatus(
                        $returnId,
                        $this->newStatusId,
                        $this->description,
                        $this->employeeId
                    );

                    $successCount++;

                    Log::debug('Estado actualizado', [
                        'batch_id' => $this->batchId,
                        'return_id' => $returnId
                    ]);

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'return_id' => $returnId,
                        'error' => $e->getMessage()
                    ];

                    Log::warning('Error actualizando estado individual', [
                        'batch_id' => $this->batchId,
                        'return_id' => $returnId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            Log::info('Actualización masiva completada', [
                'batch_id' => $this->batchId,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total_processed' => count($this->returnIds)
            ]);

            // Disparar evento de finalización
            event(new \App\Events\BulkStatusUpdateCompleted(
                $this->batchId,
                $successCount,
                $errorCount,
                $errors,
                $this->employeeId
            ));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error crítico en actualización masiva', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Job de actualización masiva falló', [
            'batch_id' => $this->batchId,
            'return_count' => count($this->returnIds),
            'error' => $exception->getMessage()
        ]);

        // Notificar al admin que inició la operación
        event(new \App\Events\BulkStatusUpdateFailed(
            $this->batchId,
            $this->returnIds,
            $exception,
            $this->employeeId
        ));
    }

    public function uniqueId()
    {
        return $this->batchId;
    }
}
