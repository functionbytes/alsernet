<?php

namespace Modules\PriceLabels\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\PriceLabels\Models\PriceLabelGeneration;

class PriceLabelGenerationCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PriceLabelGeneration $generation
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'pricelabels_generation_completed',
            'title' => 'PDF de etiquetas listo',
            'message' => "El PDF de \"{$this->generation->template_name}\" ({$this->generation->type}) esta listo para descargar.",
            'generation_id' => $this->generation->id,
            'action_url' => route('pricelabels.history.download', $this->generation),
            'icon' => 'fas fa-circle-check',
            'color' => 'success',
        ];
    }
}
