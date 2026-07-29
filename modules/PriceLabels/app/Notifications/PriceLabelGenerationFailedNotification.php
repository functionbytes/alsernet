<?php

namespace Modules\PriceLabels\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\PriceLabels\Models\PriceLabelGeneration;

class PriceLabelGenerationFailedNotification extends Notification implements ShouldQueue
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
            'type' => 'pricelabels_generation_failed',
            'title' => 'Error al generar el PDF de etiquetas',
            'message' => "No se pudo generar el PDF de \"{$this->generation->template_name}\" ({$this->generation->type}).",
            'generation_id' => $this->generation->id,
            'action_url' => route('pricelabels.history.index', ['template_id' => $this->generation->price_label_template_id]),
            'icon' => 'fas fa-circle-exclamation',
            'color' => 'danger',
        ];
    }
}
