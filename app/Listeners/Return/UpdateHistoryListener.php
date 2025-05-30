<?php

namespace App\Listeners\Return;

use App\Events\Return\ReturnStatusChanged;
use App\Models\Return\ReturnHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateHistoryListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ReturnStatusChanged $event): void
    {
        try {
            Log::info('Creating history entry', [
                'return_id' => $event->return->id_return_request,
                'previous_status' => $event->previousStatus->id_return_status,
                'new_status' => $event->newStatus->id_return_status,
                'changed_by' => $event->changedBy
            ]);

            // Crear entrada en el historial
            $historyEntry = ReturnHistory::create([
                'id_return_request' => $event->return->id_return_request,
                'id_return_status' => $event->newStatus->id_return_status,
                'description' => $this->generateDescription($event),
                'id_employee' => $event->changedBy,
                'set_pickup' => $event->newStatus->is_pickup ?? false,
                'is_refunded' => $event->newStatus->is_refunded ?? false,
                'shown_to_customer' => $event->newStatus->shown_to_customer ?? true
            ]);

            Log::info('History entry created successfully', [
                'return_id' => $event->return->id_return_request,
                'history_id' => $historyEntry->id_return_history,
                'transition_type' => $event->getTransitionType()
            ]);

            // Actualizar campos relacionados en la devolución si es necesario
            $this->updateReturnFields($event);

        } catch (\Exception $e) {
            Log::error('Failed to create history entry', [
                'return_id' => $event->return->id_return_request,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Generar descripción automática para el historial
     */
    private function generateDescription(ReturnStatusChanged $event): string
    {
        // Si ya hay una descripción personalizada, usarla
        if (!empty($event->description)) {
            return $event->description;
        }

        // Generar descripción automática basada en el cambio de estado
        $previousStatusName = $event->previousStatus->getTranslation()->name ?? 'Desconocido';
        $newStatusName = $event->newStatus->getTranslation()->name ?? 'Desconocido';

        $description = "Estado cambiado de '{$previousStatusName}' a '{$newStatusName}'";

        // Agregar información adicional según el tipo de transición
        switch ($event->getTransitionType()) {
            case 'progress':
                $description .= ' - Progreso en el proceso';
                break;
            case 'regression':
                $description .= ' - Retroceso en el proceso';
                break;
            case 'lateral':
                $description .= ' - Cambio lateral';
                break;
        }

        // Agregar metadata si existe
        if (!empty($event->metadata)) {
            $metadataInfo = [];
            foreach ($event->metadata as $key => $value) {
                $metadataInfo[] = "{$key}: {$value}";
            }
            if (!empty($metadataInfo)) {
                $description .= ' (' . implode(', ', $metadataInfo) . ')';
            }
        }

        return $description;
    }

    /**
     * Actualizar campos relacionados en la devolución
     */
    private function updateReturnFields(ReturnStatusChanged $event): void
    {
        $updateData = [];

        // Actualizar estado de reembolso si es necesario
        if ($event->shouldUpdateRefundStatus()) {
            $updateData['is_refunded'] = true;

            Log::info('Updating refund status', [
                'return_id' => $event->return->id_return_request,
                'new_refund_status' => true
            ]);
        }

        // Actualizar fecha de recogida si se programó
        if ($event->newStatus->is_pickup && empty($event->return->pickup_date)) {
            $updateData['pickup_date'] = now()->addDays(2); // Programar para 2 días después

            Log::info('Setting pickup date', [
                'return_id' => $event->return->id_return_request,
                'pickup_date' => $updateData['pickup_date']
            ]);
        }

        // Aplicar actualizaciones si hay alguna
        if (!empty($updateData)) {
            $event->return->update($updateData);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ReturnStatusChanged $event, \Throwable $exception): void
    {
        Log::critical('History update failed permanently', [
            'return_id' => $event->return->id_return_request,
            'error' => $exception->getMessage(),
            'event_data' => $event->getEventData()
        ]);
    }

    /**
     * Determinar la cola para este listener
     */
    public function viaQueue(): string
    {
        return 'default';
    }
}
