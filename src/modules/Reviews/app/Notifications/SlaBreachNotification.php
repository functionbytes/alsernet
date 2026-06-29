<?php

namespace Modules\Reviews\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Reviews\Models\Review;

class SlaBreachNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Review $review
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rating = $this->review->star_rating->value();
        $reviewedAt = $this->review->review_time;
        $slaHours = $this->review->location->sla_hours;
        $hoursOverdue = (int) now()->diffInHours($reviewedAt) - $slaHours;

        return (new MailMessage)
            ->subject('SLA incumplido: resena sin respuesta')
            ->greeting("Hola {$notifiable->name},")
            ->line('Una resena ha superado el tiempo maximo de respuesta establecido.')
            ->line("**Ubicacion:** {$this->review->location->name}")
            ->line("**Reviewer:** {$this->review->reviewer_name}")
            ->line("**Calificacion:** {$rating} estrella(s)")
            ->line("**Fecha de resena:** {$reviewedAt->format('d/m/Y H:i')}")
            ->line("**Horas de retraso:** {$hoursOverdue} hora(s) sobre el limite de {$slaHours}h")
            ->action('Ver resena', route('reviews.show', $this->review->id))
            ->line('Por favor, responde esta resena lo antes posible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'location_id' => $this->review->location_id,
            'location' => $this->review->location->name,
            'reviewer_name' => $this->review->reviewer_name,
            'rating' => $this->review->star_rating->toNumeric(),
            'review_time' => $this->review->review_time->toDateTimeString(),
            'sla_hours' => $this->review->location->sla_hours,
        ];
    }
}
