<?php

namespace Modules\Supplier\Notifications;

use Illuminate\Notifications\Notification;
use Modules\Supplier\Models\Ai\AiCost;
use Modules\Supplier\Models\Sync\SyncBatch;

class SyncBatchCompletedNotification extends Notification
{
    public function __construct(public readonly SyncBatch $batch) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $aiCost = $this->calcAiCost();

        $isOk = $this->batch->status === 'completed';
        $icon = $isOk ? 'check-circle' : 'exclamation-triangle';
        $color = $isOk ? 'success' : 'danger';

        $summary = sprintf(
            'Sync completado: %d productos procesados, %d fallidos%s',
            $this->batch->processed_items,
            $this->batch->failed_items,
            $aiCost > 0 ? sprintf(', coste IA $%.4f', $aiCost) : ''
        );

        if (!$isOk) {
            $summary = sprintf(
                'Sync fallido: %d procesados, %d fallidos%s',
                $this->batch->processed_items,
                $this->batch->failed_items,
                $aiCost > 0 ? sprintf(', coste IA $%.4f', $aiCost) : ''
            );
        }

        return [
            'type'       => 'sync_batch_completed',
            'icon'       => $icon,
            'color'      => $color,
            'title'      => $isOk ? 'Sincronización completada' : 'Sincronización fallida',
            'message'    => $summary,
            'batch_id'   => $this->batch->id,
            'batch_uid'  => $this->batch->uid,
            'batch_name' => $this->batch->batch_name,
            'status'     => $this->batch->status,
            'models'     => $this->batch->processed_items,
            'failed'     => $this->batch->failed_items,
            'duration'   => $this->batch->duration_seconds,
            'ai_cost'    => $aiCost,
            'url'        => '/settings/suppliers/sync/' . $this->batch->id,
        ];
    }

    protected function calcAiCost(): float
    {
        try {
            return (float) AiCost::where('batch_id', (string) $this->batch->id)
                ->selectRaw('SUM(COALESCE(input_cost,0) + COALESCE(output_cost,0) + COALESCE(web_search_cost,0)) as total')
                ->value('total') ?? 0.0;
        } catch (\Throwable) {
            return 0.0;
        }
    }
}
